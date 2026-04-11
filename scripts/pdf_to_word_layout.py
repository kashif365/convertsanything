import json
import os
import sys
from pathlib import Path
from statistics import median

try:
    import fitz
except ModuleNotFoundError:  # PyMuPDF >= 1.25 may expose pymupdf without fitz alias.
    import pymupdf as fitz
try:
    from rapidocr_onnxruntime import RapidOCR
except ModuleNotFoundError:
    RapidOCR = None


PX_SCALE = 96.0 / 72.0


def span_is_bold(span: dict) -> bool:
    font_name = str(span.get("font", "")).lower()
    flags = int(span.get("flags", 0))
    return (
        "bold" in font_name
        or "black" in font_name
        or "semibold" in font_name
        or "demi" in font_name
        or bool(flags & 16)
    )


def span_is_italic(span: dict) -> bool:
    font_name = str(span.get("font", "")).lower()
    flags = int(span.get("flags", 0))
    return "italic" in font_name or "oblique" in font_name or bool(flags & 2)


def normalize_span_text(text: str) -> str:
    return text.replace("\u00a0", " ").replace("\r", "")


def to_px(value: float) -> float:
    return round(value * PX_SCALE, 2)


def detect_alignment(x0: float, x1: float, page_width: float) -> str:
    line_width = max(0.0, x1 - x0)
    left_margin = max(0.0, x0)
    right_margin = max(0.0, page_width - x1)

    if line_width > 0 and line_width < page_width * 0.86:
        if abs(left_margin - right_margin) <= max(12.0, page_width * 0.03):
            return "center"

    if right_margin <= max(18.0, page_width * 0.06) and left_margin >= max(24.0, page_width * 0.12):
        return "right"

    return "left"


def spans_to_runs(spans: list[dict]) -> tuple[list[dict], str]:
    runs: list[dict] = []
    text_parts: list[str] = []
    previous_x1: float | None = None

    ordered_spans = sorted(spans, key=lambda item: float(item.get("bbox", [0, 0, 0, 0])[0]))

    for span in ordered_spans:
        text = normalize_span_text(str(span.get("text", "")))
        if text == "":
            continue

        bbox = span.get("bbox", [0, 0, 0, 0])
        x0 = float(bbox[0])
        x1 = float(bbox[2])
        span_size_pt = float(span.get("size", 12.0))
        gap = 0.0 if previous_x1 is None else max(0.0, x0 - previous_x1)

        # Approximate visual space width from font size to preserve tab-like spacing.
        space_width = max(1.8, span_size_pt * 0.30)
        gap_spaces = int(round(gap / space_width)) if gap > 0 else 0

        if gap_spaces <= 0 and previous_x1 is not None and gap > space_width * 0.55:
            gap_spaces = 1

        if gap_spaces > 0:
            text = (" " * min(gap_spaces, 8)) + text

        run = {
            "text": text,
            "fontSize": max(9, to_px(span_size_pt)),
            "fontWeight": "bold" if span_is_bold(span) else "normal",
            "fontStyle": "italic" if span_is_italic(span) else "normal",
            "fontName": str(span.get("font", "")) or "Calibri",
        }
        runs.append(run)
        text_parts.append(text)
        previous_x1 = x1

    return runs, "".join(text_parts).rstrip()


def group_lines_into_paragraphs(lines: list[dict], page_width: float) -> list[dict]:
    if not lines:
        return []

    sorted_lines = sorted(lines, key=lambda item: (item["y"], item["x"]))
    paragraphs: list[dict] = []
    current_lines: list[dict] = [sorted_lines[0]]

    for line in sorted_lines[1:]:
        previous = current_lines[-1]
        vertical_gap = line["y"] - (previous["y"] + previous["height"])
        indent_shift = abs(line["x"] - previous["x"])
        line_height = max(previous["height"], line["height"])

        starts_new_paragraph = (
            vertical_gap > max(3.0, line_height * 0.72)
            or indent_shift > max(16.0, line_height * 0.95)
        )

        if starts_new_paragraph:
            paragraphs.append(current_lines)
            current_lines = [line]
        else:
            current_lines.append(line)

    paragraphs.append(current_lines)

    result: list[dict] = []
    for paragraph_lines in paragraphs:
        x0 = min(item["x"] for item in paragraph_lines)
        y0 = min(item["y"] for item in paragraph_lines)
        x1 = max(item["x"] + item["width"] for item in paragraph_lines)
        y1 = max(item["y"] + item["height"] for item in paragraph_lines)
        line_heights = [item["height"] for item in paragraph_lines]
        alignments = [item.get("alignment", "left") for item in paragraph_lines]
        alignment = max(set(alignments), key=alignments.count)

        result.append(
            {
                "x": x0,
                "y": y0,
                "width": max(24.0, x1 - x0),
                "height": max(12.0, y1 - y0),
                "lineHeight": max(12.0, float(median(line_heights))),
                "alignment": alignment if alignment in {"left", "center", "right"} else "left",
                "lines": [{"runs": item["runs"], "text": item["text"]} for item in paragraph_lines],
            }
        )

    return result


def extract_text_page(page: fitz.Page) -> dict | None:
    page_dict = page.get_text("dict")
    page_width = float(page.rect.width)
    all_lines: list[dict] = []

    for block in page_dict.get("blocks", []):
        if block.get("type") != 0:
            continue

        for line in block.get("lines", []):
            raw_spans = line.get("spans", [])
            if not raw_spans:
                continue

            runs, line_text = spans_to_runs(raw_spans)
            if line_text.strip() == "":
                continue

            x0 = min(float(span.get("bbox", [0, 0, 0, 0])[0]) for span in raw_spans)
            y0 = min(float(span.get("bbox", [0, 0, 0, 0])[1]) for span in raw_spans)
            x1 = max(float(span.get("bbox", [0, 0, 0, 0])[2]) for span in raw_spans)
            y1 = max(float(span.get("bbox", [0, 0, 0, 0])[3]) for span in raw_spans)

            all_lines.append(
                {
                    "x": to_px(x0),
                    "y": to_px(y0),
                    "width": max(8.0, to_px(x1 - x0)),
                    "height": max(10.0, to_px(y1 - y0)),
                    "text": line_text,
                    "runs": runs,
                    "alignment": detect_alignment(x0, x1, page_width),
                }
            )

    paragraphs = group_lines_into_paragraphs(all_lines, to_px(page_width))

    if not paragraphs:
        return None

    return {
        "type": "text",
        "width": to_px(float(page.rect.width)),
        "height": to_px(float(page.rect.height)),
        "paragraphs": paragraphs,
    }


def render_page_image(page: fitz.Page, output_path: str, scale: float = 2.0) -> tuple[int, int]:
    matrix = fitz.Matrix(scale, scale)
    pix = page.get_pixmap(matrix=matrix, alpha=False)
    pix.save(output_path)
    return pix.width, pix.height


def extract_ocr_page(page: fitz.Page, output_dir: str, page_number: int, ocr: object | None) -> dict:
    image_path = os.path.join(output_dir, f"page-{page_number:03d}.png")
    width, height = render_page_image(page, image_path, 2.2)

    if ocr is None:
        return {
            "type": "ocr",
            "width": width,
            "height": height,
            "backgroundImage": image_path,
            "paragraphs": [],
        }

    result, _ = ocr(image_path)

    lines: list[dict] = []
    for item in result or []:
        box, text, _score = item
        if not str(text).strip():
            continue

        xs = [point[0] for point in box]
        ys = [point[1] for point in box]
        x = min(xs)
        y = min(ys)
        line_height = max(1, max(ys) - min(ys))

        lines.append(
            {
                "x": round(float(x), 2),
                "y": round(float(y), 2),
                "width": round(float(max(xs) - min(xs)), 2),
                "height": max(10, round(float(line_height), 2)),
                "text": str(text).strip(),
                "runs": [
                    {
                        "text": str(text).strip(),
                        "fontSize": max(11, round(line_height * 0.75, 2)),
                        "fontWeight": "normal",
                        "fontStyle": "normal",
                        "fontName": "Calibri",
                    }
                ],
                "alignment": "left",
            }
        )

    paragraphs = group_lines_into_paragraphs(lines, float(width))

    return {
        "type": "ocr",
        "width": width,
        "height": height,
        "backgroundImage": image_path,
        "paragraphs": paragraphs,
    }


def main() -> int:
    if len(sys.argv) != 4:
        print("Usage: pdf_to_word_layout.py <input.pdf> <output_dir> <output.json>", file=sys.stderr)
        return 1

    input_pdf = sys.argv[1]
    output_dir = sys.argv[2]
    output_json = sys.argv[3]

    Path(output_dir).mkdir(parents=True, exist_ok=True)

    doc = fitz.open(input_pdf)
    ocr_engine = None
    pages = []

    for page_number, page in enumerate(doc, start=1):
        text_page = extract_text_page(page)

        if text_page is not None:
            pages.append(text_page)
            continue

        if ocr_engine is None and RapidOCR is not None:
            ocr_engine = RapidOCR()

        pages.append(extract_ocr_page(page, output_dir, page_number, ocr_engine))

    with open(output_json, "w", encoding="utf-8") as handle:
        json.dump({"pages": pages}, handle, ensure_ascii=False)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
