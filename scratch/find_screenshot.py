import os

dir_path = "C:/Users/abdia/Downloads"
for filename in os.listdir(dir_path):
    if "Captura" in filename or "Captura de pantalla" in filename or "005456" in filename:
        print(f"File: {repr(filename)} - Size: {os.path.getsize(os.path.join(dir_path, filename))}")
