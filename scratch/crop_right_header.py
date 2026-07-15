from PIL import Image

im = Image.open("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header.jpg")
width, height = im.size

# Crop Section 7 & 8 (X from 2310 to 2970)
right_img = im.crop((2200, 0, 3100, height))
right_img.save("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header_right.jpg")
print("Cropped right header successfully!")
