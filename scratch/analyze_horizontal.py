from PIL import Image

im = Image.open("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header.jpg")
width, height = im.size
im_gray = im.convert("L")

# Divide width into 10 sections and count dark pixels in each section
section_width = width // 10
print(f"Header image width: {width}, height: {height}")
for i in range(10):
    x_start = i * section_width
    x_end = x_start + section_width
    dark_count = 0
    for y in range(height):
        for x in range(x_start, min(x_end, width)):
            if im_gray.getpixel((x, y)) < 240:
                dark_count += 1
    print(f"Section {i} ({x_start}-{x_end}): {dark_count} dark pixels")
