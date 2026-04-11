import json
import os
import shutil
import subprocess
import sys
from pathlib import Path


def detect_libreoffice_bin(preferred: str | None = None) -> str | None:
    candidates: list[str] = []

    if preferred:
        candidates.append(preferred)

    env_value = os.getenv("LIBREOFFICE_BIN", "").strip()
    if env_value:
        candidates.append(env_value)

    which_soffice = shutil.which("soffice")
    which_libreoffice = shutil.which("libreoffice")
    if which_soffice:
        candidates.append(which_soffice)
    if which_libreoffice:
        candidates.append(which_libreoffice)

    windows_defaults = [
        r"C:\Program Files\LibreOffice\program\soffice.exe",
        r"C:\Program Files (x86)\LibreOffice\program\soffice.exe",
    ]
    candidates.extend(windows_defaults)

    for candidate in candidates:
        candidate = candidate.strip().strip('"')
        if not candidate:
            continue

        if os.path.isfile(candidate):
            return candidate

    return None


def write_payload(output_json: str, payload: dict) -> None:
    with open(output_json, "w", encoding="utf-8") as handle:
        json.dump(payload, handle, ensure_ascii=False)


def run_libreoffice_convert(soffice_bin: str, input_file: str, output_dir: str, timeout_seconds: int = 180) -> tuple[bool, str]:
    profile_dir = Path(output_dir) / "lo-profile"
    profile_dir.mkdir(parents=True, exist_ok=True)
    profile_uri = profile_dir.resolve().as_uri()

    command = [
        soffice_bin,
        "--headless",
        "--nologo",
        "--nofirststartwizard",
        f"-env:UserInstallation={profile_uri}",
        "--convert-to",
        "pdf:writer_pdf_Export",
        "--outdir",
        output_dir,
        input_file,
    ]

    result = subprocess.run(
        command,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        timeout=max(30, timeout_seconds),
        shell=False,
    )

    combined = (result.stdout or "") + "\n" + (result.stderr or "")
    return result.returncode == 0, combined.strip()


def find_output_pdf(input_file: str, output_dir: str) -> str | None:
    stem = Path(input_file).stem
    expected = Path(output_dir) / f"{stem}.pdf"

    if expected.is_file():
        return str(expected)

    # LibreOffice occasionally normalizes names, so fall back to first PDF in output dir.
    pdf_files = sorted(Path(output_dir).glob("*.pdf"))
    if pdf_files:
        return str(pdf_files[0])

    return None


def main() -> int:
    if len(sys.argv) < 4:
        print(
            "Usage: word_to_pdf_libreoffice.py <input.doc|input.docx> <output_dir> <output.json> [libreoffice_bin]",
            file=sys.stderr,
        )
        return 1

    input_file = sys.argv[1]
    output_dir = sys.argv[2]
    output_json = sys.argv[3]
    preferred_soffice = sys.argv[4] if len(sys.argv) > 4 else ""

    Path(output_dir).mkdir(parents=True, exist_ok=True)

    if not os.path.isfile(input_file):
        write_payload(
            output_json,
            {
                "success": False,
                "errorCode": "input_missing",
                "message": "Input Word file was not found.",
            },
        )
        return 2

    soffice_bin = detect_libreoffice_bin(preferred_soffice)
    if soffice_bin is None:
        write_payload(
            output_json,
            {
                "success": False,
                "errorCode": "libreoffice_missing",
                "message": "LibreOffice CLI (soffice) was not found on this machine.",
            },
        )
        return 2

    try:
        success, details = run_libreoffice_convert(soffice_bin, input_file, output_dir)
    except subprocess.TimeoutExpired:
        write_payload(
            output_json,
            {
                "success": False,
                "errorCode": "timeout",
                "message": "Conversion timed out while running LibreOffice.",
            },
        )
        return 2
    except Exception as exc:
        write_payload(
            output_json,
            {
                "success": False,
                "errorCode": "execution_error",
                "message": f"Failed to run LibreOffice conversion: {exc}",
            },
        )
        return 2

    if not success:
        write_payload(
            output_json,
            {
                "success": False,
                "errorCode": "conversion_failed",
                "message": "LibreOffice failed to convert the document to PDF.",
                "details": details,
            },
        )
        return 2

    pdf_path = find_output_pdf(input_file, output_dir)
    if pdf_path is None:
        write_payload(
            output_json,
            {
                "success": False,
                "errorCode": "output_missing",
                "message": "Conversion completed but PDF output file was not found.",
            },
        )
        return 2

    write_payload(
        output_json,
        {
            "success": True,
            "pdfPath": pdf_path,
            "libreOfficeBin": soffice_bin,
        },
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
