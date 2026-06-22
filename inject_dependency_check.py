import os
import re

directories = ['lestravida-checkout', 'lestravida-kegiatan', 'lestravida-certificate']

for dir_name in directories:
    if not os.path.exists(dir_name):
        continue
    for filename in os.listdir(dir_name):
        if filename.endswith('.php'):
            filepath = os.path.join(dir_name, filename)
            with open(filepath, 'r') as f:
                content = f.read()
            
            # Check if it already has the check
            if "if (!class_exists('WooCommerce')) return;" in content:
                continue

            # Inject into hooks(): void {
            new_content = re.sub(
                r'(public static function hooks\(\)(?:\s*:\s*void)?\s*\{)',
                r'\1\n        if (!class_exists(\'WooCommerce\')) return;',
                content
            )

            # Inject into init() { inside settings.php
            if filename == 'settings.php':
                new_content = re.sub(
                    r'(public static function init\(\)\s*\{)',
                    r'\1\n        if (!class_exists(\'WooCommerce\')) return;',
                    new_content
                )

            if new_content != content:
                with open(filepath, 'w') as f:
                    f.write(new_content)
                print(f"Updated {filepath}")

# Update lestravida-checkout.php
with open('lestravida-checkout.php', 'r') as f:
    content = f.read()
    if "if (!class_exists('WooCommerce')) return;" not in content:
        new_content = re.sub(
            r'(function lvc_register_admin_menu\(\)(?:\s*:\s*void)?\s*\{)',
            r'\1\n    if (!class_exists(\'WooCommerce\')) return;',
            content
        )
        with open('lestravida-checkout.php', 'w') as f:
            f.write(new_content)
        print("Updated lestravida-checkout.php")

# Update lestravida-kegiatan.php
with open('lestravida-kegiatan.php', 'r') as f:
    content = f.read()
    if "if (!class_exists('WooCommerce')) return;" not in content:
        new_content = re.sub(
            r'(function lvk_register_admin_menu\(\)(?:\s*:\s*void)?\s*\{)',
            r'\1\n    if (!class_exists(\'WooCommerce\')) return;',
            content
        )
        with open('lestravida-kegiatan.php', 'w') as f:
            f.write(new_content)
        print("Updated lestravida-kegiatan.php")

