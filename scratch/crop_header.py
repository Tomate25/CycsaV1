from PIL import Image

im = Image.open("C:/xampp/htdocs/Cycsa/publico/img/hoja_horizontal.jpg")
width, height = im.size

# Crop top 600 pixels
header_img = im.crop((0, 0, width, 600))
header_img.save("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header.jpg")
print("Cropped header successfully!")
