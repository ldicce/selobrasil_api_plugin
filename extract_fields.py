#!/usr/bin/env python3
"""
Script to extract form fields from serpro-cnpj-quotas.php and generate
 the fields array for integrations-config.php
"""

import re

# Read the plugin file
with open('/Users/dicce/Documents/Projetos/serproapi/serpro-cnpj-quotas-v3.2/serpro-cnpj-quotas.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Find all shortcode functions
pattern = r"function (\w+)_shortcode\([^)]*\)\s*\{[^}]*serc_render_form\(\s*'(\w+)',\s*array\((.*?)\)\s*\)"
matches = re.findall(pattern, content, re.DOTALL)

output = {}

for func_name, type_name, fields_str in matches:
    # Parse individual field arrays
    field_pattern = r"array\s*\((.*?)\s*\)"
    field_matches = re.findall(field_pattern, fields_str, re.DOTALL)
    
    fields = []
    for field_str in field_matches:
        field = {}
        # Extract field properties
        props = re.findall(r"'(\w+)'\s*=>\s*(?:'([^']*)'|(true|false))", field_str)
        for key, val1, val2 in props:
            value = val1 if val1 else val2
            if value == 'true':
                field[key] = True
            elif value == 'false':
                field[key] = False
            else:
                field[key] = value
        
        if field:
            fields.append(field)
    
    if fields:
        output[type_name] = fields

# Print the PHP array format
print("//" + "=" * 70)
print("// Generated form fields - add to integrations-config.php")
print("//" + "=" * 70)
for type_name, fields in sorted(output.items()):
    print(f"\n// {type_name}")
    print("'fields' => [")
    for field in fields:
        print("    [", end="")
        props = []
        for key, val in field.items():
            if isinstance(val, bool):
                props.append(f"'{key}' => {str(val).lower()}")
            else:
                props.append(f"'{key}' => '{val}'")
        print(", ".join(props), end="")
        print("],")
    print("],")

print("\n// Total types with fields:", len(output))
