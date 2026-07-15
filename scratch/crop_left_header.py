from PIL import Image

im = Image.open("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header.jpg")
width, height = im.size

# Crop Section 0 & 1 (X from 0 to 660)
left_img = im.crop((0, 0, 700, height))
left_img.save("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header_left.jpg")
print("Cropped left header successfully!")
