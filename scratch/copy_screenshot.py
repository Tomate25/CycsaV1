import shutil
import os

src = "C:/Users/abdia/Downloads/Captura de pantalla 2026-07-15 005456.png"
dst = "C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/screenshot.png"

if os.path.exists(src):
    shutil.copy(src, dst)
    print("Copied successfully in Python!")
else:
    print(f"Source does not exist: {src}")
