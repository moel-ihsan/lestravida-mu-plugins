import os
directories = ["lestravida-checkout", "lestravida-kegiatan", "lestravida-certificate", "."]
for d in directories:
    if not os.path.exists(d): continue
    for f in os.listdir(d):
        if f.endswith(".php"):
            path = os.path.join(d, f)
            if os.path.isfile(path):
                with open(path, "r") as file:
                    c = file.read()
                new_c = c.replace(r"class_exists(\'WooCommerce\')", "class_exists('WooCommerce')")
                if new_c != c:
                    with open(path, "w") as file:
                        file.write(new_c)
