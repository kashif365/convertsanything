(function () {
  "use strict";

  const apiBase = (window.appConfig && window.appConfig.apiBaseUrl) || "/api";
  const LAST_TOOL_KEY = "convertsanything:lastTool";

  /* ─── Global loader ─── */
  function ensureGlobalLoader() {
    let loader = document.querySelector("[data-global-loader]");
    if (loader) return loader;
    loader = document.createElement("div");
    loader.className = "global-loader";
    loader.setAttribute("data-global-loader", "");
    loader.setAttribute("hidden", "hidden");
    loader.innerHTML = `
      <div class="global-loader-card" role="status" aria-live="polite">
        <div class="spinner"></div>
        <h4>Processing your file...</h4>
        <p>Please keep this tab open until processing completes.</p>
      </div>`;
    document.body.appendChild(loader);
    return loader;
  }

  function setGlobalLoading(isLoading) {
    const loader = ensureGlobalLoader();
    loader.hidden = !isLoading;
    loader.style.display = isLoading ? "grid" : "none";
    loader.setAttribute("aria-hidden", isLoading ? "false" : "true");
    document.body.classList.toggle("is-loading", isLoading);
  }

  /* ─── HTTP helpers ─── */
  const postJson = async (endpoint, payload) => {
    const res = await fetch(`${apiBase}${endpoint}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": window.appConfig.csrfToken,
      },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.message || data.error || "Request failed");
    return data;
  };

  const postForm = async (endpoint, formData) => {
    const res = await fetch(`${apiBase}${endpoint}`, {
      method: "POST",
      body: formData,
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN": window.appConfig.csrfToken,
      },
    });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.message || data.error || "Request failed");
    return data;
  };

  /* ─── Render download results ─── */
  function renderDownloadResults(resultsRoot, items) {
    resultsRoot.innerHTML = "";
    setGlobalLoading(false);

    (items || []).forEach((item) => {
      const row = document.createElement("div");
      row.className = "result-item";

      const preview = item.previewUrl
        ? `<figure class="result-preview"><img src="${item.previewUrl}" alt="${item.filename || "Preview"}" loading="lazy"></figure>`
        : "";
      const sizeLabel = item.sizeLabel
        ? `<span class="result-size">${item.sizeLabel}</span>`
        : "";

      row.innerHTML = `
        ${preview}
        <main>
          <h4>${item.filename || "Processed file ready"}</h4>
          ${sizeLabel}
          <p>${item.note || item.downloadUrl || ""}</p>
        </main>`;

      const actions = document.createElement("div");
      actions.className = "item-actions";

      if (item.downloadUrl) {
        const link = document.createElement("a");
        link.href = item.downloadUrl;
        link.textContent = "Download";
        link.setAttribute("download", item.filename || "download");
        actions.appendChild(link);
      }
      if (item.previewUrl) {
        const pl = document.createElement("a");
        pl.href = item.previewUrl;
        pl.textContent = "Open";
        pl.target = "_blank";
        pl.rel = "noopener noreferrer";
        actions.appendChild(pl);
      }
      if (item.fallback && item.fallback.downloadUrl) {
        const fb = document.createElement("a");
        fb.href = item.fallback.downloadUrl;
        fb.textContent = "PNG Fallback";
        fb.setAttribute("download", item.fallback.filename || "fallback.png");
        actions.appendChild(fb);
      }

      row.appendChild(actions);
      resultsRoot.appendChild(row);
    });

    requestAnimationFrame(() => setGlobalLoading(false));
  }

  /* ─── File manager (dropzone) ─── */
  function createFileManager(root) {
    const state = { files: [] };
    const blobUrls = new Map(); /* file → object URL for image previews */
    const list = root.querySelector("[data-file-list]");
    const error = root.querySelector("[data-error]");
    const dropzone = root.querySelector("[data-dropzone]");
    const hideDropzoneOnFiles = dropzone && dropzone.dataset.hideOnFiles === "true";
    const input = dropzone.querySelector('input[type="file"]');
    let dropzoneFiles = null;

    const getBlobUrl = (file) => {
      if (!file.type.startsWith("image/")) return null;
      if (!blobUrls.has(file)) blobUrls.set(file, URL.createObjectURL(file));
      return blobUrls.get(file);
    };

    const revokeFile = (file) => {
      const url = blobUrls.get(file);
      if (url) { URL.revokeObjectURL(url); blobUrls.delete(file); }
    };

    if (dropzone) {
      dropzoneFiles = dropzone.querySelector("[data-dropzone-files]");
      if (!dropzoneFiles) {
        dropzoneFiles = document.createElement("div");
        dropzoneFiles.className = "dropzone-files";
        dropzoneFiles.setAttribute("data-dropzone-files", "");
        dropzoneFiles.hidden = true;
        dropzone.appendChild(dropzoneFiles);
      }
      Array.from(dropzone.children).forEach((child) => {
        if (child === input || child === dropzoneFiles) return;
        child.setAttribute("data-dropzone-default", "");
      });
    }

    const subscribers = [];
    const multiple = dropzone.dataset.multiple === "true";
    const accept = (dropzone.dataset.accept || "").split(",").map((v) => v.trim()).filter(Boolean);
    const maxSize = Number(dropzone.dataset.maxSize || 10485760);

    const showError = (message) => {
      if (!error) return;
      error.hidden = !message;
      error.textContent = message || "";
    };

    const isValidFile = (file) =>
      accept.some((type) => {
        if (type.startsWith(".")) return file.name.toLowerCase().endsWith(type.toLowerCase());
        if (type.endsWith("/*")) return file.type.startsWith(type.slice(0, -1));
        return file.type === type;
      });

    const syncButtons = () => {
      root.querySelectorAll("[data-convert],[data-submit],[data-ocr-submit]").forEach((btn) => {
        btn.disabled =
          state.files.length === 0 ||
          (root.dataset.tool === "pdf-merge" && state.files.length < 2);
      });
    };

    const notify = () => subscribers.forEach((fn) => { try { fn([...state.files]); } catch (_) {} });

    const render = () => {
      if (!list) return;
      list.innerHTML = "";
      if (dropzoneFiles) dropzoneFiles.innerHTML = "";

      if (hideDropzoneOnFiles && dropzone) {
        dropzone.hidden = false;
        list.hidden = true;
      }

      if (state.files.length === 0) {
        if (hideDropzoneOnFiles && dropzone) {
          dropzone.classList.remove("has-files");
          if (dropzoneFiles) dropzoneFiles.hidden = true;
        }
        if (!hideDropzoneOnFiles) {
          const empty = document.createElement("div");
          empty.className = "file-item empty";
          empty.innerHTML = `<main><h4>No file selected</h4><p>Add a file to begin.</p></main>`;
          list.appendChild(empty);
        }
        notify();
        return;
      }

      state.files.forEach((file, index) => {
        const thumbUrl = getBlobUrl(file);
        const item = document.createElement("div");
        item.className = (hideDropzoneOnFiles ? "file-item in-dropzone" : "file-item") + (thumbUrl ? " has-thumb" : "");

        if (thumbUrl) {
          const img = document.createElement("img");
          img.src = thumbUrl;
          img.className = "file-thumb";
          img.alt = file.name;
          item.appendChild(img);
        }

        const main = document.createElement("main");
        main.innerHTML = `<h4>${file.name}</h4><p>${formatBytes(file.size)} &middot; Ready</p>`;
        item.appendChild(main);

        const actionsDiv = document.createElement("div");
        actionsDiv.className = "item-actions";
        item.appendChild(actionsDiv);

        const actions = actionsDiv;

        if (root.dataset.tool === "pdf-merge") {
          [["Up", -1], ["Down", 1]].forEach(([label, step]) => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.textContent = label;
            btn.addEventListener("click", () => {
              const target = index + step;
              if (target < 0 || target >= state.files.length) return;
              const next = [...state.files];
              const moved = next.splice(index, 1)[0];
              next.splice(target, 0, moved);
              state.files = next;
              render();
              syncButtons();
            });
            actions.appendChild(btn);
          });
        }

        const remove = document.createElement("button");
        remove.type = "button";
        remove.textContent = "Remove";
        remove.addEventListener("click", () => {
          revokeFile(state.files[index]);
          state.files.splice(index, 1);
          render();
          syncButtons();
        });
        actions.appendChild(remove);

        if (hideDropzoneOnFiles && dropzoneFiles) {
          dropzoneFiles.appendChild(item);
        } else {
          list.appendChild(item);
        }
      });

      if (hideDropzoneOnFiles && dropzone) {
        dropzone.classList.add("has-files");
        if (dropzoneFiles) dropzoneFiles.hidden = false;
      }
      notify();
    };

    const addFiles = (incoming) => {
      showError("");
      const batch = Array.from(incoming || []);
      const valid = [];
      for (const file of batch) {
        if (!isValidFile(file)) { showError(`Invalid file type: ${file.name}`); continue; }
        if (file.size > maxSize) { showError(`File too large: ${file.name}`); continue; }
        valid.push(file);
      }
      state.files = multiple ? [...state.files, ...valid] : valid.slice(0, 1);
      render();
      syncButtons();
    };

    input.addEventListener("change", (e) => { addFiles(e.target.files); input.value = ""; });
    ["dragenter", "dragover"].forEach((n) => dropzone.addEventListener(n, (e) => { e.preventDefault(); dropzone.classList.add("dragover"); }));
    ["dragleave", "drop"].forEach((n) => dropzone.addEventListener(n, (e) => { e.preventDefault(); dropzone.classList.remove("dragover"); }));
    dropzone.addEventListener("drop", (e) => addFiles(e.dataTransfer.files));

    const subscribe = (fn) => {
      subscribers.push(fn);
      fn([...state.files]);
      return () => { const i = subscribers.indexOf(fn); if (i !== -1) subscribers.splice(i, 1); };
    };

    const reset = () => {
      showError("");
      state.files.forEach(revokeFile);
      blobUrls.clear();
      state.files = [];
      render();
      syncButtons();
    };

    render();
    syncButtons();
    return { state, showError, syncButtons, subscribe, reset };
  }

  /* ─── Scale slider: read real image dimensions ─── */
  async function getImageNaturalDims(file) {
    return new Promise((resolve) => {
      if (!file || !file.type.startsWith("image/")) return resolve(null);
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => { URL.revokeObjectURL(url); resolve({ w: img.naturalWidth, h: img.naturalHeight }); };
      img.onerror = () => { URL.revokeObjectURL(url); resolve(null); };
      img.src = url;
    });
  }

  function setupScaleSlider(root, manager) {
    const scaleRange = root.querySelector("[data-scale-range]");
    const scaleValue = root.querySelector("[data-scale-value]");
    const scaleDims  = root.querySelector("[data-scale-dims]");
    const widthInput = root.querySelector("[data-width]");
    const heightInput = root.querySelector("[data-height]");

    if (!scaleRange || !scaleValue) return;

    let sourceDims = null;

    const updateDimsLabel = () => {
      const pct = parseInt(scaleRange.value, 10);
      scaleValue.textContent = pct + "%";

      if (!scaleDims) return;

      if (!sourceDims) {
        scaleDims.textContent = "Upload an image to see output dimensions";
        return;
      }

      const outW = Math.max(1, Math.round(sourceDims.w * pct / 100));
      const outH = Math.max(1, Math.round(sourceDims.h * pct / 100));

      if (pct === 100) {
        scaleDims.textContent = `${sourceDims.w} × ${sourceDims.h} px (original)`;
      } else {
        scaleDims.textContent = `${sourceDims.w} × ${sourceDims.h} → ${outW} × ${outH} px`;
      }

      /* sync width/height inputs on resizer tool */
      if (widthInput && heightInput) {
        widthInput.value  = outW;
        heightInput.value = outH;
      }
    };

    scaleRange.addEventListener("input", updateDimsLabel);

    manager.subscribe(async (files) => {
      if (!files.length) {
        sourceDims = null;
        updateDimsLabel();
        return;
      }
      sourceDims = await getImageNaturalDims(files[0]);
      updateDimsLabel();
    });

    updateDimsLabel();
  }

  /* ─── Image tools (compress, resize, convert, jpg-to-png, png-to-webp) ─── */
  function setupImageTool(root, mode) {
    const manager      = createFileManager(root);
    const convertBtn   = root.querySelector("[data-convert]");
    const results      = root.querySelector("[data-results]");
    const status       = root.querySelector("[data-status]");
    const qualityRange = root.querySelector("[data-quality-range]");
    const qualityValue = root.querySelector("[data-quality-value]");
    const scaleRange   = root.querySelector("[data-scale-range]");
    const formatSelect = root.querySelector("[data-format]");
    const widthInput   = root.querySelector("[data-width]");
    const heightInput  = root.querySelector("[data-height]");
    const aspectLock   = root.querySelector("[data-lock-aspect]");
    const estimatedSize= root.querySelector("[data-estimated-size]");

    let activeRequest = 0;
    let estimateToken = 0;
    let estimateTimer = null;
    const estimateCache = new Map();

    /* set up scale slider */
    setupScaleSlider(root, manager);

    const normalizeSourceMime = (mime) => {
      if (!mime || typeof mime !== "string") return "image/jpeg";
      if (mime === "image/jpg") return "image/jpeg";
      if (mime.startsWith("image/")) return mime;
      return "image/jpeg";
    };

    const toBlobAsync = (canvas, format, quality) =>
      new Promise((resolve) => {
        if (format === "image/png") { canvas.toBlob((b) => resolve(b), format); return; }
        canvas.toBlob((b) => resolve(b), format, quality);
      });

    const estimateEncodedFileSize = async (file, targetFormat, qualityFraction) => {
      const key = `${file.name}:${file.size}:${file.lastModified}:${targetFormat}:${qualityFraction ?? "na"}`;
      if (estimateCache.has(key)) return estimateCache.get(key);
      if (!file.type.startsWith("image/")) return file.size;

      let bitmap;
      try {
        bitmap = await createImageBitmap(file);
        const maxDim = 2500;
        const scale  = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
        const w = Math.max(1, Math.round(bitmap.width * scale));
        const h = Math.max(1, Math.round(bitmap.height * scale));
        const canvas = document.createElement("canvas");
        canvas.width = w; canvas.height = h;
        canvas.getContext("2d").drawImage(bitmap, 0, 0, w, h);
        const blob = await toBlobAsync(canvas, targetFormat, qualityFraction);
        const size = blob ? blob.size : Math.max(1024, Math.round(file.size * 0.65));
        estimateCache.set(key, size);
        return size;
      } catch (_) {
        const fallback = Math.max(1024, Math.round(file.size * 0.65));
        estimateCache.set(key, fallback);
        return fallback;
      } finally {
        if (bitmap && typeof bitmap.close === "function") bitmap.close();
      }
    };

    const resolveEstimateTarget = () => {
      if (mode === "jpg-to-png") return "image/png";
      if (mode === "png-to-webp") return "image/webp";
      if (mode === "compressor") {
        if (!formatSelect || formatSelect.value === "auto") return "auto-source";
        return formatSelect.value;
      }
      if (mode === "image-converter") {
        const cf = root.querySelector("[data-convert-format]");
        return cf ? cf.value : "image/webp";
      }
      return "image/webp";
    };

    const estimateOutputSize = () => {
      if (!estimatedSize) return;
      if (!manager.state.files.length) { estimatedSize.textContent = "Estimated output size: —"; return; }

      const token = ++estimateToken;
      const targetFormat = resolveEstimateTarget();
      const qualityNum = Number(qualityRange ? qualityRange.value : 85);
      const qualityFraction = targetFormat === "image/png" ? undefined : Math.max(0.1, Math.min(1, qualityNum / 100));
      const scalePct = scaleRange ? parseInt(scaleRange.value, 10) : 100;
      const files = [...manager.state.files];

      estimatedSize.textContent = "Estimating...";
      clearTimeout(estimateTimer);
      estimateTimer = setTimeout(async () => {
        const sample = files.slice(0, 4);
        let sampleSource = 0, sampleEstimated = 0;
        const totalSource = files.reduce((s, f) => s + (f.size || 0), 0);

        for (const file of sample) {
          sampleSource += file.size || 0;
          const effectiveTarget = targetFormat === "auto-source" ? normalizeSourceMime(file.type) : targetFormat;

          let est;
          if (mode === "resizer" || scalePct !== 100) {
            /* scale affects dimensions, estimate proportionally */
            const sr = (scalePct / 100) ** 2;
            const baseEst = await estimateEncodedFileSize(file, effectiveTarget === "auto-source" ? "image/jpeg" : effectiveTarget, qualityFraction);
            est = Math.max(1024, Math.round(baseEst * sr));
          } else {
            est = await estimateEncodedFileSize(file, effectiveTarget, qualityFraction);
          }

          sampleEstimated += est;
          if (token !== estimateToken) return;
        }

        let totalEst = sampleEstimated;
        if (files.length > sample.length && sampleSource > 0) {
          totalEst += Math.round((sampleEstimated / sampleSource) * Math.max(0, totalSource - sampleSource));
        }
        if (token !== estimateToken) return;
        estimatedSize.textContent = `Estimated output size: ${formatBytes(Math.max(1024, totalEst))}`;
      }, 130);
    };

    if (qualityRange && qualityValue) {
      qualityRange.addEventListener("input", () => { qualityValue.textContent = `${qualityRange.value}%`; estimateOutputSize(); });
    }
    if (scaleRange) scaleRange.addEventListener("input", estimateOutputSize);
    if (formatSelect) formatSelect.addEventListener("change", estimateOutputSize);
    const convertFormatSelect = root.querySelector("[data-convert-format]");
    if (convertFormatSelect) convertFormatSelect.addEventListener("change", estimateOutputSize);
    if (widthInput) widthInput.addEventListener("input", estimateOutputSize);
    if (heightInput) heightInput.addEventListener("input", estimateOutputSize);
    if (aspectLock) aspectLock.addEventListener("change", estimateOutputSize);

    const setStatus = (message, type = "idle") => {
      if (!status) return;
      status.hidden = !message;
      status.textContent = message || "";
      status.classList.remove("success", "loading", "error");
      if (type) status.classList.add(type);
    };

    setStatus("Ready. Upload a file and hit Convert.");
    manager.subscribe(() => estimateOutputSize());

    /* Collect elements to hide during "converted" state */
    const collectUploadEls = () => [
      root.querySelector("[data-dropzone]"),
      ...root.querySelectorAll(".option-bar"),
      ...root.querySelectorAll(".option-grid"),
      root.querySelector(".action-row.left"),   /* estimated-size row */
      root.querySelector(".tool-notes"),
    ].filter(Boolean);

    const hideUploadZone = () => collectUploadEls().forEach((el) => { el.hidden = true; });
    const showUploadZone = () => collectUploadEls().forEach((el) => { el.hidden = false; });

    const showResetZone = () => {
      /* remove any existing reset zone */
      const existing = root.querySelector(".reset-zone");
      if (existing) existing.remove();

      const zone = document.createElement("div");
      zone.className = "reset-zone";
      zone.innerHTML = `<button class="button secondary" type="button">Convert another file</button>`;
      zone.querySelector("button").addEventListener("click", () => {
        /* reset back to upload state */
        results.innerHTML = "";
        zone.remove();
        showUploadZone();
        const convertRow = convertBtn.closest(".action-row");
        if (convertRow) convertRow.hidden = false;
        if (status) status.hidden = true;
        manager.reset();
        estimateOutputSize();
      });

      results.after(zone);
    };

    convertBtn.addEventListener("click", async () => {
      if (!manager.state.files.length) return;
      activeRequest += 1;
      const requestId = activeRequest;

      const formData = new FormData();
      manager.state.files.forEach((file, i) => formData.append(`files[${i}]`, file));

      const scale = scaleRange ? parseInt(scaleRange.value, 10) : 100;
      formData.append("scale", String(scale));

      let endpoint = "/images/jpg-to-png";

      if (mode === "png-to-webp") {
        endpoint = "/images/png-to-webp";
        if (qualityRange) formData.append("quality", qualityRange.value);
      } else if (mode === "compressor") {
        endpoint = "/images/compress";
        if (qualityRange) formData.append("quality", qualityRange.value);
        if (formatSelect) formData.append("format", formatSelect.value);
      } else if (mode === "resizer") {
        endpoint = "/images/resize";
        formData.append("width",  widthInput  ? widthInput.value  : "1200");
        formData.append("height", heightInput ? heightInput.value : "800");
        formData.append("lock_aspect", aspectLock && aspectLock.checked ? "1" : "0");
      } else if (mode === "image-converter") {
        endpoint = "/images/convert";
        if (qualityRange) formData.append("quality", qualityRange.value);
        if (convertFormatSelect) formData.append("format", convertFormatSelect.value);
      }

      manager.showError("");
      results.innerHTML = "";
      convertBtn.disabled = true;
      setGlobalLoading(true);
      setStatus("Processing your image...", "loading");

      try {
        const data = await postForm(endpoint, formData);

        /* ── Smooth transition: hide upload zone, reveal results ── */
        hideUploadZone();
        const convertRow = convertBtn.closest(".action-row");
        if (convertRow) convertRow.hidden = true;
        if (status) status.hidden = true;

        renderDownloadResults(results, data.downloads || []);
        showResetZone();

        /* scroll results smoothly into view */
        requestAnimationFrame(() => results.scrollIntoView({ behavior: "smooth", block: "nearest" }));
      } catch (err) {
        manager.showError(err.message || "Processing failed.");
        setGlobalLoading(false);
        setStatus("Processing failed. Please check the error and try again.", "error");
        manager.syncButtons();
      } finally {
        if (requestId === activeRequest) setGlobalLoading(false);
      }
    });
  }

  /* ─── Image-to-Text (OCR) tool ─── */
  function setupOcrTool(root) {
    const manager    = createFileManager(root);
    const submitBtn  = root.querySelector("[data-ocr-submit]");
    const langSelect = root.querySelector("[data-ocr-language]");
    const status     = root.querySelector("[data-status]");
    const resultWrap = root.querySelector("[data-ocr-result]");
    const outputArea = root.querySelector("[data-ocr-output]");
    const copyBtn    = root.querySelector("[data-ocr-copy]");
    const dlBtn      = root.querySelector("[data-ocr-download]");
    const clearBtn   = root.querySelector("[data-ocr-clear]");
    const wordCount  = root.querySelector("[data-ocr-word-count]");
    const charCount  = root.querySelector("[data-ocr-char-count]");

    const setStatus = (msg, type = "idle") => {
      if (!status) return;
      status.hidden = !msg;
      status.textContent = msg || "";
      status.classList.remove("success", "loading", "error");
      if (type) status.classList.add(type);
    };

    setStatus("Ready. Upload an image containing text.");

    submitBtn.addEventListener("click", async () => {
      if (!manager.state.files.length) return;

      const file = manager.state.files[0];
      const formData = new FormData();
      formData.append("file", file);
      if (langSelect) formData.append("language", langSelect.value);

      submitBtn.disabled = true;
      if (resultWrap) resultWrap.hidden = true;
      manager.showError("");
      setGlobalLoading(true);
      setStatus("Extracting text from your image...", "loading");

      try {
        const data = await postForm("/images/ocr", formData);

        if (outputArea) outputArea.value = data.text || "";
        if (wordCount)  wordCount.textContent  = `${data.words || 0} words`;
        if (charCount)  charCount.textContent  = `${data.chars || 0} characters`;
        if (resultWrap) resultWrap.hidden = false;

        setStatus("Text extracted successfully.", "success");
      } catch (err) {
        manager.showError(err.message || "OCR extraction failed.");
        setStatus("Extraction failed. Please check the error above.", "error");
      } finally {
        setGlobalLoading(false);
        manager.syncButtons();
      }
    });

    if (copyBtn && outputArea) {
      copyBtn.addEventListener("click", async () => {
        if (!outputArea.value) return;
        try {
          await navigator.clipboard.writeText(outputArea.value);
          const original = copyBtn.textContent;
          copyBtn.textContent = "Copied!";
          setTimeout(() => { copyBtn.textContent = original; }, 1500);
        } catch (_) {
          outputArea.select();
          document.execCommand("copy");
        }
      });
    }

    if (dlBtn && outputArea) {
      dlBtn.addEventListener("click", () => {
        const text = outputArea.value;
        if (!text) return;
        const blob = new Blob([text], { type: "text/plain" });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement("a");
        a.href     = url;
        a.download = "extracted-text.txt";
        a.click();
        URL.revokeObjectURL(url);
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener("click", () => {
        if (outputArea) outputArea.value = "";
        if (resultWrap) resultWrap.hidden = true;
        if (wordCount)  wordCount.textContent = "";
        if (charCount)  charCount.textContent = "";
        setStatus("Ready. Upload an image containing text.");
      });
    }
  }

  /* ─── Word counter ─── */
  function renderWordCounter(root) {
    const input = root.querySelector("[data-text-input]");
    const stats = root.querySelector("[data-stats]");
    const copy  = root.querySelector("[data-copy]");
    const clear = root.querySelector("[data-clear]");
    let timer = null;

    const calculate = async () => {
      try {
        const data = await postJson("/text/analyze", { text: input.value });
        stats.innerHTML = (data.stats || [])
          .map((item) => `<div class="stat-card"><strong>${item.value}</strong><span>${item.label}</span></div>`)
          .join("");
      } catch (_) {}
    };

    input.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(calculate, 200); });
    calculate();

    if (copy) {
      copy.addEventListener("click", async () => {
        await navigator.clipboard.writeText(input.value);
        const orig = copy.textContent;
        copy.textContent = "Copied!";
        setTimeout(() => { copy.textContent = orig; }, 1500);
      });
    }
    if (clear) { clear.addEventListener("click", () => { input.value = ""; calculate(); }); }
  }

  /* ─── Case converter ─── */
  function renderCaseConverter(root) {
    const input = root.querySelector("[data-text-input]");
    const copy  = root.querySelector("[data-copy]");
    const clear = root.querySelector("[data-clear]");

    root.querySelectorAll("[data-case]").forEach((btn) => {
      btn.addEventListener("click", async () => {
        try {
          const data = await postJson("/text/case-convert", { text: input.value, mode: btn.dataset.case });
          input.value = data.text || "";
        } catch (_) {}
      });
    });

    if (copy) {
      let t = null;
      copy.addEventListener("click", async () => {
        await navigator.clipboard.writeText(input.value);
        const orig = copy.textContent;
        copy.textContent = "Copied!";
        clearTimeout(t);
        t = setTimeout(() => { copy.textContent = orig; }, 1500);
      });
    }
    if (clear) { clear.addEventListener("click", () => { input.value = ""; }); }
  }

  /* ─── PDF split range builder ─── */
  function buildRangeRow(index, start = "", end = "") {
    const row = document.createElement("div");
    row.className = "range-row";
    row.innerHTML = `
      <strong>#${index + 1}</strong>
      <input type="number" min="1" placeholder="Start" value="${start}" data-range-start>
      <span>to</span>
      <input type="number" min="1" placeholder="End" value="${end}" data-range-end>
      <button type="button">Remove</button>`;
    return row;
  }

  /* ─── Document tools ─── */
  function submitDocumentTool(root, endpoint, mode) {
    const manager    = createFileManager(root);
    const submit     = root.querySelector("[data-submit]");
    const results    = root.querySelector("[data-results]");
    const rangesWrap = root.querySelector("[data-ranges]");
    const addRange   = root.querySelector("[data-add-range]");
    const status     = root.querySelector("[data-status]");
    const dropzone   = root.querySelector("[data-dropzone]");

    const labels = {
      "pdf-to-word": { idle: "Convert PDF to Word",  processing: "Converting to DOCX...", done: "Converted successfully" },
      "word-to-pdf": { idle: "Convert Word to PDF",  processing: "Converting to PDF...",  done: "Converted successfully" },
      "pdf-merge":   { idle: "Merge PDFs",            processing: "Merging PDFs...",        done: "PDFs merged successfully" },
      "pdf-split":   { idle: "Split PDF",             processing: "Splitting PDF...",       done: "PDF split successfully" },
    };

    const setStatus = (msg, type = "idle") => {
      if (!status) return;
      status.hidden = !msg;
      status.textContent = msg || "";
      status.classList.remove("success", "loading", "error");
      if (type) status.classList.add(type);
    };

    const setBusy = (busy) => {
      submit.disabled = busy;
      submit.textContent = busy ? labels[mode].processing : labels[mode].idle;
      if (dropzone) dropzone.classList.toggle("busy", busy);
      if (addRange) addRange.disabled = busy;
      root.querySelectorAll(".range-row button").forEach((b) => {
        b.disabled = busy || root.querySelectorAll(".range-row").length === 1;
      });
      setGlobalLoading(busy);
    };

    setStatus("Ready. Upload a file and start.");

    if (mode === "pdf-split") {
      const syncRows = () => {
        rangesWrap.querySelectorAll(".range-row").forEach((row, i) => {
          row.querySelector("strong").textContent = `#${i + 1}`;
          row.querySelector("button").disabled = rangesWrap.querySelectorAll(".range-row").length === 1;
        });
      };

      const createRow = (start, end) => {
        const row = buildRangeRow(rangesWrap.querySelectorAll(".range-row").length, start, end);
        row.querySelector("button").addEventListener("click", () => { row.remove(); syncRows(); });
        row.querySelectorAll("input").forEach((inp) => inp.addEventListener("input", () => {
          manager.showError("");
          setStatus("Range updated. Ready to split.");
        }));
        return row;
      };

      rangesWrap.appendChild(createRow("1", "1"));
      syncRows();
      addRange.addEventListener("click", () => { rangesWrap.appendChild(createRow("", "")); syncRows(); });
    }

    submit.addEventListener("click", async () => {
      if (!manager.state.files.length) return;

      manager.showError("");
      results.innerHTML = "";
      setBusy(true);
      setStatus(labels[mode].processing, "loading");

      const formData = new FormData();
      if (mode === "pdf-merge") {
        manager.state.files.forEach((file, i) => formData.append(`files[${i}]`, file));
      } else {
        formData.append("file", manager.state.files[0]);
      }

      if (mode === "pdf-split") {
        const ranges = [...rangesWrap.querySelectorAll(".range-row")]
          .map((row) => `${row.querySelector("[data-range-start]").value}-${row.querySelector("[data-range-end]").value}`)
          .filter((v) => /^\d+-\d+$/.test(v));

        if (!ranges.length) {
          manager.showError("Please add at least one valid page range.");
          setStatus("Fix page ranges before processing.", "error");
          setBusy(false);
          manager.syncButtons();
          return;
        }

        const invalid = ranges.some((v) => {
          const [s, e] = v.split("-").map(Number);
          return isNaN(s) || isNaN(e) || s < 1 || e < s;
        });

        if (invalid) {
          manager.showError("Each range must have valid numbers and End ≥ Start.");
          setStatus("Correct invalid ranges.", "error");
          setBusy(false);
          manager.syncButtons();
          return;
        }

        formData.append("ranges", ranges.join(","));
      }

      try {
        const data = await postForm(endpoint, formData);
        renderDownloadResults(results, data.downloads || [{ filename: data.filename, downloadUrl: data.downloadUrl, note: data.message }]);
        setStatus(labels[mode].done, "success");
      } catch (err) {
        manager.showError(err.message || "Request failed.");
        setStatus("Processing failed. Please check the error and try again.", "error");
      } finally {
        manager.syncButtons();
        setBusy(false);
      }
    });
  }

  /* ─── Returning visitor card ─── */
  function setupReturningVisitorCard() {
    const card   = document.querySelector("[data-last-tool-card]");
    const text   = document.querySelector("[data-last-tool-text]");
    const link   = document.querySelector("[data-last-tool-link]");
    const cancel = document.querySelector("[data-last-tool-cancel]");
    if (!card || !text || !link) return;

    const raw = localStorage.getItem(LAST_TOOL_KEY);
    if (!raw) return;

    try {
      const parsed = JSON.parse(raw);
      if (!parsed || !parsed.href || !parsed.title) return;
      card.hidden = false;
      text.textContent = `Last used: ${parsed.title}`;
      link.href = parsed.href;
      if (cancel) cancel.addEventListener("click", () => { card.hidden = true; });
    } catch (_) {
      localStorage.removeItem(LAST_TOOL_KEY);
    }
  }

  /* ─── Tool tab filter ─── */
  function setupToolTabs() {
    const tabs  = Array.from(document.querySelectorAll("[data-tool-tab]"));
    const cards = Array.from(document.querySelectorAll("[data-tool-category]"));
    if (!tabs.length || !cards.length) return;

    const applyFilter = (filter) => {
      cards.forEach((card) => {
        card.hidden = !(filter === "all" || card.dataset.toolCategory === filter);
      });
      tabs.forEach((tab) => {
        const active = tab.dataset.toolTab === filter;
        tab.classList.toggle("is-active", active);
        tab.setAttribute("aria-selected", active ? "true" : "false");
      });
    };

    tabs.forEach((tab) => tab.addEventListener("click", () => applyFilter(tab.dataset.toolTab || "all")));
    applyFilter("all");
  }

  /* ─── Helpers ─── */
  function formatBytes(value) {
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
  }

  /* ─── Bootstrap ─── */
  document.querySelectorAll(".tool-app").forEach((root) => {
    const tool = root.dataset.tool;

    if (["jpg-to-png", "png-to-webp", "compressor", "resizer", "image-converter"].includes(tool)) {
      setupImageTool(root, tool);
    }

    if (tool === "img-to-text")   setupOcrTool(root);
    if (tool === "word-counter")  renderWordCounter(root);
    if (tool === "case-converter") renderCaseConverter(root);
    if (tool === "pdf-to-word")   submitDocumentTool(root, "/convert/pdf-to-word", tool);
    if (tool === "word-to-pdf")   submitDocumentTool(root, "/convert/word-to-pdf", tool);
    if (tool === "pdf-merge")     submitDocumentTool(root, "/pdf/merge", tool);
    if (tool === "pdf-split")     submitDocumentTool(root, "/pdf/split", tool);

    /* save last visited tool */
    const heading = document.querySelector(".tool-heading h1");
    if (heading && window.location.pathname.startsWith("/tools/")) {
      localStorage.setItem(LAST_TOOL_KEY, JSON.stringify({
        href: window.location.pathname,
        title: heading.textContent.trim(),
        visitedAt: Date.now(),
      }));
    }
  });

  setupReturningVisitorCard();
  setupToolTabs();
})();
