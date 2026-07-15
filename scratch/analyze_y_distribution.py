from PIL import Image

im = Image.open("C:/xampp/htdocs/Cycsa/publico/img/hoja_horizontal.jpg")
width, height = im.size
im_gray = im.convert("L")

# Count dark pixels in blocks of 50 lines
for y_start in range(0, 1200, 50):
    dark_count = 0
    for y in range(y_start, y_start + 50):
        if y >= height:
            break
        for x in range(width):
            if im_gray.getpixel((x, y)) < 240:
                dark_count += 1
    print(f"Lines {y_start:04d}-{y_start+50:04d}: {dark_count} dark pixels")
