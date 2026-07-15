from PIL import Image

im = Image.open("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header.jpg")
width, height = im.size
im_gray = im.convert("L")

# Find Y coordinates where there's a horizontal line (high density of dark pixels across the width)
for y in range(height):
    dark_pixels = sum(1 for x in range(width) if im_gray.getpixel((x, y)) < 240)
    if dark_pixels > width * 0.5: # If more than 50% of the width is dark
        print(f"Horizontal line detected at Y = {y} ({dark_pixels} dark pixels)")
