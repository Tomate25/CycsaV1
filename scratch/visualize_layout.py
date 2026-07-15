from PIL import Image, ImageDraw

im = Image.open("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header.jpg")
width, height = im.size

draw = ImageDraw.Draw(im)

# 2.8cm on A4 is: 2.8 / 29.7 * 2550 = 240 pixels
y_28 = int(2.8 / 29.7 * 2550)
draw.line([(0, y_28), (width, y_28)], fill="red", width=5)
draw.text((100, y_28 + 10), "Linea de 2.8cm (Padding actual)", fill="red")

# 3.2cm on A4 is: 3.2 / 29.7 * 2550 = 274 pixels
y_32 = int(3.2 / 29.7 * 2550)
draw.line([(0, y_32), (width, y_32)], fill="blue", width=5)
draw.text((100, y_32 + 10), "Linea de 3.2cm", fill="blue")

# 3.6cm on A4 is: 3.6 / 29.7 * 2550 = 309 pixels
y_36 = int(3.6 / 29.7 * 2550)
draw.line([(0, y_36), (width, y_36)], fill="green", width=5)
draw.text((100, y_36 + 10), "Linea de 3.6cm", fill="green")

# 4.0cm on A4 is: 4.0 / 29.7 * 2550 = 343 pixels
y_40 = int(4.0 / 29.7 * 2550)
draw.line([(0, y_40), (width, y_40)], fill="purple", width=5)
draw.text((100, y_40 + 10), "Linea de 4.0cm", fill="purple")

im.save("C:/Users/abdia/.gemini/antigravity-cli/brain/051e1a8c-2c81-4aa8-ab5a-f9cc836e7eee/hoja_horizontal_header_lines.jpg")
print("Visualized lines successfully!")
