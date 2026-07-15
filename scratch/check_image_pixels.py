from PIL import Image

im = Image.open("C:/xampp/htdocs/Cycsa/publico/img/hoja_horizontal.jpg")
width, height = im.size
print(f"Dimensions: {width}x{height}")

# Convert to grayscale to check for non-white pixels in the top 300 pixels
im_gray = im.convert("L")
non_white = 0
for y in range(min(300, height)):
    for x in range(width):
        p = im_gray.getpixel((x, y))
        if p < 240: # If pixel is dark
            non_white += 1

print(f"Non-white pixels in top 300px: {non_white}")
