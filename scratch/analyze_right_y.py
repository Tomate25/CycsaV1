from PIL import Image

im = Image.open("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header_right.jpg")
width, height = im.size
im_gray = im.convert("L")

# Print rows of 20 pixels with dark pixel counts
for y_start in range(0, height, 20):
    dark_count = sum(1 for y in range(y_start, min(y_start+20, height)) for x in range(width) if im_gray.getpixel((x, y)) < 240)
    print(f"Y Rows {y_start:03d}-{y_start+20:03d}: {dark_count} dark pixels")
