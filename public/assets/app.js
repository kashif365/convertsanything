(function () {
  const apiBase = (window.appConfig && window.appConfig.apiBaseUrl) || "/api";
  const LAST_TOOL_KEY = "covertsanything:lastTool";

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
        <p>Please keep this tab open until conversion completes.</p>
      </div>
    `;

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

  const postJson = async (endpoint, payload) => {
    const response = await fetch(`${apiBase}${endpoint}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": window.appConfig.csrfToken,
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || data.error || "Request failed");
    }

    return data;
  };

  const postForm = async (endpoint, formData) => {
    const response = await fetch(`${apiBase}${endpoint}`, {
      method: "POST",
      body: formData,
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN": window.appConfig.csrfToken,
      },
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || data.error || "Request failed");
    }

    return data;
  };

  function renderDownloadResults(resultsRoot, items) {
    resultsRoot.innerHTML = "";
    setGlobalLoading(false);

    (items || []).forEach((item) => {
      const row = document.createElement("div");
      row.className = "result-item";
      const preview = item.previewUrl
        ? `<figure class="result-preview"><img src="${item.previewUrl}" alt="${item.filename || "Preview"}" loading="lazy"></figure>`
        : "";
      const sizeLabel = item.sizeLabel ? `<span class="result-size">${item.sizeLabel}</span>` : "";
      row.innerHTML = `
        ${preview}
        <main>
          <h4>${item.filename || "Processed file ready"}</h4>
          ${sizeLabel}
          <p>${item.note || item.downloadUrl || "Server returned success."}</p>
        </main>
      `;

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
        const previewLink = document.createElement("a");
        previewLink.href = item.previewUrl;
        previewLink.textContent = "Open";
        previewLink.target = "_blank";
        previewLink.rel = "noopener noreferrer";
        actions.appendChild(previewLink);
      }

      if (item.fallback && item.fallback.downloadUrl) {
        const fallbackLink = document.createElement("a");
        fallbackLink.href = item.fallback.downloadUrl;
        fallbackLink.textContent = "PNG Fallback";
        fallbackLink.setAttribute("download", item.fallback.filename || "fallback.png");
        actions.appendChild(fallbackLink);
      }

      row.appendChild(actions);
      resultsRoot.appendChild(row);
    });

    // Keep loader state tied to painted output to avoid visual stuck overlays.
    requestAnimationFrame(() => setGlobalLoading(false));
  }

  function createFileManager(root) {
    const state = { files: [] };
    const list = root.querySelector("[data-file-list]");
    const error = root.querySelector("[data-error]");
    const dropzone = root.querySelector("[data-dropzone]");
    const hideDropzoneOnFiles = dropzone && dropzone.dataset.hideOnFiles === "true";
    const input = dropzone.querySelector('input[type="file"]');
    let dropzoneFiles = null;

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
    const accept = (dropzone.dataset.accept || "")
      .split(",")
      .map((value) => value.trim())
      .filter(Boolean);
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
      root.querySelectorAll("[data-convert], [data-submit]").forEach((btn) => {
        btn.disabled = state.files.length === 0 || (root.dataset.tool === "pdf-merge" && state.files.length < 2);
      });
    };

    const notify = () => {
      subscribers.forEach((listener) => {
        try {
          listener([...state.files]);
        } catch (_) {
        }
      });
    };

    const render = () => {
      if (!list) return;

      list.innerHTML = "";
      if (dropzoneFiles) {
        dropzoneFiles.innerHTML = "";
      }

      if (hideDropzoneOnFiles && dropzone) {
        dropzone.hidden = false;
        list.hidden = true;
      }

      if (state.files.length === 0) {
        if (hideDropzoneOnFiles && dropzone) {
          dropzone.classList.remove("has-files");
          if (dropzoneFiles) {
            dropzoneFiles.hidden = true;
          }
        }

        if (!hideDropzoneOnFiles) {
          const empty = document.createElement("div");
          empty.className = "file-item empty";
          empty.innerHTML = `
            <main>
              <h4>No files selected</h4>
              <p>Add files to start processing.</p>
            </main>
          `;
          list.appendChild(empty);
        }

        notify();
        return;
      }

      state.files.forEach((file, index) => {
        const item = document.createElement("div");
        item.className = hideDropzoneOnFiles ? "file-item in-dropzone" : "file-item";
        item.innerHTML = `
          <main>
            <h4>${file.name}</h4>
            <p>${formatBytes(file.size)} · Ready</p>
          </main>
          <div class="item-actions"></div>
        `;

        const actions = item.querySelector(".item-actions");

        if (root.dataset.tool === "pdf-merge") {
          [["Up", -1], ["Down", 1]].forEach(([label, step]) => {
            const button = document.createElement("button");
            button.type = "button";
            button.textContent = label;
            button.addEventListener("click", () => {
              const target = index + step;
              if (target < 0 || target >= state.files.length) return;
              const next = [...state.files];
              const moved = next.splice(index, 1)[0];
              next.splice(target, 0, moved);
              state.files = next;
              render();
              syncButtons();
            });
            actions.appendChild(button);
          });
        }

        const remove = document.createElement("button");
        remove.type = "button";
        remove.textContent = "Remove";
        remove.addEventListener("click", () => {
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
        if (dropzoneFiles) {
          dropzoneFiles.hidden = false;
        }
      }

      notify();
    };

    const addFiles = (incoming) => {
      showError("");
      const batch = Array.from(incoming || []);
      const valid = [];

      for (const file of batch) {
        if (!isValidFile(file)) {
          showError(`Invalid file type: ${file.name}`);
          continue;
        }
        if (file.size > maxSize) {
          showError(`File too large: ${file.name}`);
          continue;
        }
        valid.push(file);
      }

      state.files = multiple ? [...state.files, ...valid] : valid.slice(0, 1);
      render();
      syncButtons();
    };

    input.addEventListener("change", (event) => {
      addFiles(event.target.files);
      input.value = "";
    });

    ["dragenter", "dragover"].forEach((name) =>
      dropzone.addEventListener(name, (event) => {
        event.preventDefault();
        dropzone.classList.add("dragover");
      }),
    );

    ["dragleave", "drop"].forEach((name) =>
      dropzone.addEventListener(name, (event) => {
        event.preventDefault();
        dropzone.classList.remove("dragover");
      }),
    );

    dropzone.addEventListener("drop", (event) => addFiles(event.dataTransfer.files));

    const subscribe = (listener) => {
      subscribers.push(listener);
      listener([...state.files]);

      return () => {
        const index = subscribers.indexOf(listener);
        if (index !== -1) {
          subscribers.splice(index, 1);
        }
      };
    };

    render();
    syncButtons();

    return { state, showError, syncButtons, subscribe };
  }

  function setupImageTool(root, mode) {
    const manager = createFileManager(root);
    const convertBtn = root.querySelector("[data-convert]");
    const results = root.querySelector("[data-results]");
    const status = root.querySelector("[data-status]");
    const qualityRange = root.querySelector("[data-quality-range]");
    const qualityValue = root.querySelector("[data-quality-value]");
    const formatSelect = root.querySelector("[data-format]");
    const widthInput = root.querySelector("[data-width]");
    const heightInput = root.querySelector("[data-height]");
    const aspectLock = root.querySelector("[data-lock-aspect]");
    const estimatedSize = root.querySelector("[data-estimated-size]");
    let activeImageRequest = 0;
    let estimateToken = 0;
    let estimateTimer = null;
    const estimateCache = new Map();

    const normalizeSourceMime = (mime) => {
      if (!mime || typeof mime !== "string") return "image/jpeg";
      if (mime === "image/jpg") return "image/jpeg";
      if (mime.startsWith("image/")) return mime;
      return "image/jpeg";
    };

    const toBlobAsync = (canvas, format, quality) =>
      new Promise((resolve) => {
        if (format === "image/png") {
          canvas.toBlob((blob) => resolve(blob), format);
          return;
        }
        canvas.toBlob((blob) => resolve(blob), format, quality);
      });

    const estimateEncodedFileSize = async (file, targetFormat, qualityFraction) => {
      const key = `${file.name}:${file.size}:${file.lastModified}:${targetFormat}:${qualityFraction ?? "na"}`;
      if (estimateCache.has(key)) {
        return estimateCache.get(key);
      }

      if (!file.type.startsWith("image/")) {
        return file.size;
      }

      let bitmap;
      try {
        bitmap = await createImageBitmap(file);
        const maxDimension = 2500;
        const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext("2d");
        context.drawImage(bitmap, 0, 0, width, height);
        const blob = await toBlobAsync(canvas, targetFormat, qualityFraction);
        const size = blob ? blob.size : Math.max(1024, Math.round(file.size * 0.65));
        estimateCache.set(key, size);
        return size;
      } catch (_) {
        const fallback = Math.max(1024, Math.round(file.size * 0.65));
        estimateCache.set(key, fallback);
        return fallback;
      } finally {
        if (bitmap && typeof bitmap.close === "function") {
          bitmap.close();
        }
      }
    };

    const estimateResizedFileSize = async (file) => {
      if (!file.type.startsWith("image/")) {
        return file.size;
      }

      let bitmap;
      try {
        bitmap = await createImageBitmap(file);
        const sourceWidth = Math.max(1, bitmap.width || 1);
        const sourceHeight = Math.max(1, bitmap.height || 1);
        const sourcePixels = sourceWidth * sourceHeight;

        const requestedWidth = Math.max(1, Number(widthInput && widthInput.value ? widthInput.value : sourceWidth));
        const requestedHeight = Math.max(1, Number(heightInput && heightInput.value ? heightInput.value : sourceHeight));

        let targetWidth = requestedWidth;
        let targetHeight = requestedHeight;

        if (aspectLock && aspectLock.checked) {
          const sourceAspect = sourceWidth / sourceHeight;
          const widthRatio = requestedWidth / sourceWidth;
          const heightRatio = requestedHeight / sourceHeight;
          const ratio = Math.min(widthRatio, heightRatio);
          targetWidth = Math.max(1, Math.round(sourceWidth * ratio));
          targetHeight = Math.max(1, Math.round(sourceHeight * ratio));
        }

        const targetPixels = Math.max(1, targetWidth * targetHeight);
        const pixelRatio = Math.max(0.03, Math.min(4, targetPixels / sourcePixels));
        const format = normalizeSourceMime(file.type);
        const formatFactor = format === "image/png" ? 1.08 : format === "image/webp" ? 0.78 : 0.88;
        return Math.max(1024, Math.round((file.size || 0) * Math.pow(pixelRatio, 0.92) * formatFactor));
      } catch (_) {
        return Math.max(1024, Math.round((file.size || 0) * 0.75));
      } finally {
        if (bitmap && typeof bitmap.close === "function") {
          bitmap.close();
        }
      }
    };

    const resolveEstimateTarget = () => {
      if (mode === "jpg-to-png") {
        return "image/png";
      }
      if (mode === "png-to-webp") {
        return "image/webp";
      }
      if (mode === "compressor") {
        if (!formatSelect || formatSelect.value === "auto") {
          return "auto-source";
        }
        return formatSelect.value;
      }
      if (mode === "image-converter") {
        const convertFormat = root.querySelector("[data-convert-format]");
        return convertFormat ? convertFormat.value : "image/webp";
      }
      return "image/webp";
    };

    const estimateOutputSize = () => {
      if (!estimatedSize) return;

      if (!manager.state.files.length) {
        estimatedSize.textContent = "Estimated output size: -";
        return;
      }

      const token = ++estimateToken;
      const targetFormat = resolveEstimateTarget();
      const qualityNumber = Number(qualityRange ? qualityRange.value : 85);
      const qualityFraction = targetFormat === "image/png" ? undefined : Math.max(0.1, Math.min(1, qualityNumber / 100));
      const files = [...manager.state.files];

      estimatedSize.textContent = "Estimating output size...";
      clearTimeout(estimateTimer);
      estimateTimer = setTimeout(async () => {
        const sampleLimit = 4;
        const sample = files.slice(0, sampleLimit);
        const totalSource = files.reduce((sum, file) => sum + (file.size || 0), 0);

        let sampleSource = 0;
        let sampleEstimated = 0;

        for (const file of sample) {
          sampleSource += file.size || 0;
          if (mode === "resizer") {
            sampleEstimated += await estimateResizedFileSize(file);
          } else {
            const effectiveTarget =
              targetFormat === "auto-source"
                ? normalizeSourceMime(file.type)
                : targetFormat;
            sampleEstimated += await estimateEncodedFileSize(file, effectiveTarget, qualityFraction);
          }
          if (token !== estimateToken) {
            return;
          }
        }

        let estimatedBytes = sampleEstimated;
        if (files.length > sample.length && sampleSource > 0) {
          const remainingSource = Math.max(0, totalSource - sampleSource);
          estimatedBytes += Math.round((sampleEstimated / sampleSource) * remainingSource);
        }

        if (token !== estimateToken) {
          return;
        }

        estimatedSize.textContent = `Estimated output size: ${formatBytes(Math.max(1024, estimatedBytes))}`;
      }, 130);
    };

    if (qualityRange && qualityValue) {
      qualityRange.addEventListener("input", () => {
        qualityValue.textContent = `${qualityRange.value}%`;
        estimateOutputSize();
      });
    }

    if (formatSelect) {
      formatSelect.addEventListener("change", estimateOutputSize);
    }

    if (widthInput) {
      widthInput.addEventListener("input", estimateOutputSize);
    }

    if (heightInput) {
      heightInput.addEventListener("input", estimateOutputSize);
    }

    if (aspectLock) {
      aspectLock.addEventListener("change", estimateOutputSize);
    }

    const convertFormatSelect = root.querySelector("[data-convert-format]");
    if (convertFormatSelect) {
      convertFormatSelect.addEventListener("change", estimateOutputSize);
    }

    const setStatus = (message, type = "idle") => {
      if (!status) return;
      status.hidden = !message;
      status.textContent = message || "";
      status.classList.remove("success", "loading", "error");
      if (type) status.classList.add(type);
    };

    setStatus("Ready. Upload your file and start.");
    manager.subscribe(() => {
      estimateOutputSize();
    });

    convertBtn.addEventListener("click", async () => {
      if (!manager.state.files.length) return;
      activeImageRequest += 1;
      const requestId = activeImageRequest;

      const formData = new FormData();
      manager.state.files.forEach((file, index) => formData.append(`files[${index}]`, file));

      let endpoint = "/images/jpg-to-png";

      if (mode === "png-to-webp") {
        endpoint = "/images/png-to-webp";
        formData.append("quality", qualityRange.value);
      } else if (mode === "compressor") {
        endpoint = "/images/compress";
        formData.append("quality", qualityRange.value);
        formData.append("format", formatSelect.value);
      } else if (mode === "resizer") {
        endpoint = "/images/resize";
        formData.append("width", widthInput.value);
        formData.append("height", heightInput.value);
        formData.append("lock_aspect", aspectLock.checked ? "1" : "0");
      } else if (mode === "image-converter") {
        endpoint = "/images/convert";
        const convertFormat = root.querySelector("[data-convert-format]");
        formData.append("quality", qualityRange ? qualityRange.value : "85");
        formData.append("format", convertFormat.value);
      }

      manager.showError("");
      results.innerHTML = "";
      convertBtn.disabled = true;
      setGlobalLoading(true);
      setStatus("Processing your file...", "loading");

      try {
        const data = await postForm(endpoint, formData);
        renderDownloadResults(results, data.downloads || []);
        setStatus("File processed successfully.", "success");
        estimateOutputSize();
      } catch (error) {
        manager.showError(error.message || "Processing failed");
        setGlobalLoading(false);
        setStatus("Processing failed. Please review the error and try again.", "error");
      } finally {
        if (requestId === activeImageRequest) {
          setGlobalLoading(false);
        }
        manager.syncButtons();
      }
    });
  }

  function renderWordCounter(root) {
    const input = root.querySelector("[data-text-input]");
    const stats = root.querySelector("[data-stats]");
    const copy = root.querySelector("[data-copy]");
    const clear = root.querySelector("[data-clear]");
    let timer = null;

    const calculate = async () => {
      try {
        const data = await postJson("/text/analyze", { text: input.value });
        stats.innerHTML = (data.stats || [])
          .map((item) => `<div class="stat-card"><strong>${item.value}</strong><span>${item.label}</span></div>`)
          .join("");
      } catch (_) {
      }
    };

    input.addEventListener("input", () => {
      clearTimeout(timer);
      timer = setTimeout(calculate, 200);
    });

    calculate();

    clear.addEventListener("click", () => {
      input.value = "";
      calculate();
    });
  }

  function renderCaseConverter(root) {
    const input = root.querySelector("[data-text-input]");
    const copy = root.querySelector("[data-copy]");
    const clear = root.querySelector("[data-clear]");

    root.querySelectorAll("[data-case]").forEach((button) => {
      button.addEventListener("click", async () => {
        try {
          const data = await postJson("/text/case-convert", {
            text: input.value,
            mode: button.dataset.case,
          });
          input.value = data.text || "";
        } catch (_) {
        }
      });
    });

    if (copy) {
      let copyTimer = null;
      copy.addEventListener("click", async () => {
        await navigator.clipboard.writeText(input.value);
        const original = copy.textContent;
        copy.textContent = "Copied";
        clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
          copy.textContent = original;
        }, 1500);
      });
    }
    clear.addEventListener("click", () => {
      input.value = "";
    });
  }

  function buildRangeRow(index, start = "", end = "") {
    const row = document.createElement("div");
    row.className = "range-row";
    row.innerHTML = `
      <strong>#${index + 1}</strong>
      <input type="number" min="1" placeholder="Start" value="${start}" data-range-start>
      <span>to</span>
      <input type="number" min="1" placeholder="End" value="${end}" data-range-end>
      <button type="button">Remove</button>
    `;
    return row;
  }

  function submitDocumentTool(root, endpoint, mode) {
    const manager = createFileManager(root);
    const submit = root.querySelector("[data-submit]");
    const results = root.querySelector("[data-results]");
    const rangesWrap = root.querySelector("[data-ranges]");
    const addRange = root.querySelector("[data-add-range]");
    const status = root.querySelector("[data-status]");
    const dropzone = root.querySelector("[data-dropzone]");

    const labels = {
      "pdf-to-word": {
        idle: "Convert PDF to Word",
        processing: "Converting to DOCX...",
        done: "PDF converted successfully",
      },
      "word-to-pdf": {
        idle: "Convert Word to PDF",
        processing: "Converting to PDF...",
        done: "Word converted successfully",
      },
      "pdf-merge": {
        idle: "Merge PDFs",
        processing: "Merging PDFs...",
        done: "PDFs merged successfully",
      },
      "pdf-split": {
        idle: "Split PDF",
        processing: "Splitting PDF...",
        done: "PDF split successfully",
      },
    };

    const setStatus = (message, type = "idle") => {
      if (!status) return;
      status.hidden = !message;
      status.textContent = message || "";
      status.classList.remove("success", "loading", "error");
      if (type) status.classList.add(type);
    };

    const setBusy = (busy) => {
      submit.disabled = busy;
      submit.textContent = busy ? labels[mode].processing : labels[mode].idle;
      if (dropzone) {
        dropzone.classList.toggle("busy", busy);
      }
      if (addRange) {
        addRange.disabled = busy;
      }
      root.querySelectorAll(".range-row button").forEach((button) => {
        button.disabled = busy || root.querySelectorAll(".range-row").length === 1;
      });
      setGlobalLoading(busy);
    };

    setStatus("Ready. Upload your file and start.");

    if (mode === "pdf-split") {
      const syncRows = () => {
        rangesWrap.querySelectorAll(".range-row").forEach((row, index) => {
          row.querySelector("strong").textContent = `#${index + 1}`;
          row.querySelector("button").disabled = rangesWrap.querySelectorAll(".range-row").length === 1;
        });
      };

      const createRow = (start, end) => {
        const row = buildRangeRow(rangesWrap.querySelectorAll(".range-row").length, start, end);
        row.querySelector("button").addEventListener("click", () => {
          row.remove();
          syncRows();
        });
        row.querySelectorAll("input").forEach((input) => {
          input.addEventListener("input", () => {
            manager.showError("");
            setStatus("Range updated. Ready to split.");
          });
        });
        return row;
      };

      rangesWrap.appendChild(createRow("1", "1"));
      syncRows();

      addRange.addEventListener("click", () => {
        rangesWrap.appendChild(createRow("", ""));
        syncRows();
      });
    }

    submit.addEventListener("click", async () => {
      if (!manager.state.files.length) return;

      manager.showError("");
      results.innerHTML = "";
      setBusy(true);
      setStatus(labels[mode].processing, "loading");

      const formData = new FormData();

      if (mode === "pdf-merge") {
        manager.state.files.forEach((file, index) => formData.append(`files[${index}]`, file));
      } else {
        formData.append("file", manager.state.files[0]);
      }

      if (mode === "pdf-split") {
        const ranges = [...rangesWrap.querySelectorAll(".range-row")]
          .map((row) => `${row.querySelector("[data-range-start]").value}-${row.querySelector("[data-range-end]").value}`)
          .filter((value) => /^\d+-\d+$/.test(value));

        if (!ranges.length) {
          manager.showError("Please add at least one valid page range.");
          setStatus("Please fix page ranges before processing.", "error");
          manager.syncButtons();
          setBusy(false);
          return;
        }

        const hasInvalidRange = ranges.some((value) => {
          const [start, end] = value.split("-").map((item) => Number(item));
          return Number.isNaN(start) || Number.isNaN(end) || start < 1 || end < start;
        });

        if (hasInvalidRange) {
          manager.showError("Each range must have valid numbers and End must be greater than or equal to Start.");
          setStatus("Please correct invalid ranges.", "error");
          setBusy(false);
          manager.syncButtons();
          return;
        }

        formData.append("ranges", ranges.join(","));
      }

      try {
        const data = await postForm(endpoint, formData);
        renderDownloadResults(
          results,
          data.downloads || [{ filename: data.filename, downloadUrl: data.downloadUrl, note: data.message }],
        );
        setStatus(labels[mode].done, "success");
      } catch (error) {
        manager.showError(error.message || "Request failed");
        setStatus("Processing failed. Please review the error and try again.", "error");
      } finally {
        manager.syncButtons();
        setBusy(false);
      }
    });
  }

  function setupReturningVisitorCard() {
    const card = document.querySelector("[data-last-tool-card]");
    const text = document.querySelector("[data-last-tool-text]");
    const link = document.querySelector("[data-last-tool-link]");
    const cancel = document.querySelector("[data-last-tool-cancel]");

    if (!card || !text || !link) return;

    const raw = localStorage.getItem(LAST_TOOL_KEY);
    if (!raw) return;

    try {
      const parsed = JSON.parse(raw);
      if (!parsed || !parsed.href || !parsed.title) return;
      card.hidden = false;
      text.textContent = `Last tool: ${parsed.title}`;
      link.href = parsed.href;

      if (cancel) {
        cancel.addEventListener("click", () => {
          card.hidden = true;
        });
      }
    } catch (_) {
      localStorage.removeItem(LAST_TOOL_KEY);
    }
  }

  function setupToolTabs() {
    const tabs = Array.from(document.querySelectorAll("[data-tool-tab]"));
    const cards = Array.from(document.querySelectorAll("[data-tool-category]"));

    if (!tabs.length || !cards.length) return;

    const applyFilter = (filter) => {
      cards.forEach((card) => {
        const cardCategory = card.dataset.toolCategory;
        card.hidden = !(filter === "all" || cardCategory === filter);
      });

      tabs.forEach((tab) => {
        const isActive = tab.dataset.toolTab === filter;
        tab.classList.toggle("is-active", isActive);
        tab.setAttribute("aria-selected", isActive ? "true" : "false");
      });
    };

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        applyFilter(tab.dataset.toolTab || "all");
      });
    });

    applyFilter("all");
  }

  function formatBytes(value) {
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
  }

  document.querySelectorAll(".tool-app").forEach((root) => {
    const tool = root.dataset.tool;

    if (tool === "jpg-to-png" || tool === "png-to-webp" || tool === "compressor" || tool === "resizer" || tool === "image-converter") {
      setupImageTool(root, tool);
    }

    if (tool === "word-counter") renderWordCounter(root);
    if (tool === "case-converter") renderCaseConverter(root);
    if (tool === "pdf-to-word") submitDocumentTool(root, "/convert/pdf-to-word", tool);
    if (tool === "word-to-pdf") submitDocumentTool(root, "/convert/word-to-pdf", tool);
    if (tool === "pdf-merge") submitDocumentTool(root, "/pdf/merge", tool);
    if (tool === "pdf-split") submitDocumentTool(root, "/pdf/split", tool);

    const toolHeading = document.querySelector(".tool-heading h1");
    if (toolHeading && window.location.pathname.startsWith("/tools/")) {
      localStorage.setItem(
        LAST_TOOL_KEY,
        JSON.stringify({
          href: window.location.pathname,
          title: toolHeading.textContent.trim(),
          visitedAt: Date.now(),
        }),
      );
    }
  });

  setupReturningVisitorCard();
  setupToolTabs();
})();
