"""Package deployable theme files without repository tooling or credentials."""

from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    slug = "generatepress_child"
    required = ("style.css", "functions.php", "inc/mapbox-settings.php", "inc/google-maps-settings.php", "inc/post-types.php")
    for name in required:
        if not (root / name).is_file():
            raise SystemExit(f"Required theme file is missing: {name}")

    # Root templates/assets plus explicit source directories. Never package
    # .git, workflows, docs, tests, dist, or local configuration files.
    extensions = {".php", ".css", ".js", ".json", ".png", ".jpg", ".jpeg", ".gif", ".webp", ".svg", ".ico", ".woff", ".woff2", ".ttf"}
    candidates = list(root.iterdir())
    for name in ("inc", "assets"):
        directory = root / name
        if directory.is_symlink():
            raise SystemExit(f"Theme source must not contain symbolic links: {name}")
        if directory.is_dir():
            candidates.extend(directory.rglob("*"))
    files = []
    for path in sorted(candidates):
        if path.is_symlink():
            raise SystemExit(f"Theme source must not contain symbolic links: {path}")
        relative = path.relative_to(root)
        if path.is_file() and path.suffix.lower() in extensions and not any(part.startswith(".") for part in relative.parts):
            files.append(path)

    output = root / "dist" / f"{slug}.zip"
    output.parent.mkdir(parents=True, exist_ok=True)
    with ZipFile(output, "w", compression=ZIP_DEFLATED) as archive:
        for path in files:
            archive.write(path, f"{slug}/{path.relative_to(root).as_posix()}")
    print(f"Built {output} ({len(files)} files)")


if __name__ == "__main__":
    main()
