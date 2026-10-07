-- 002: starting categories and settings (safe to run twice)

INSERT IGNORE INTO categories (name, slug, description, sort_order) VALUES
    ('Tuff Tiles',    'tuff-tiles',    'Durable tiles for floors, paths and outdoor areas.', 1),
    ('Doors',         'doors',         'Doors for homes, shops and offices.',                2),
    ('Gardens',       'gardens',       'Products for gardens and outdoor spaces.',           3),
    ('Metal Gates',   'metal-gates',   'Metal gates and grills made to last.',               4),
    ('Roof Ceilings', 'roof-ceilings', 'Roof and ceiling solutions for every building.',     5);

-- delivery_charge_paisa starts at 0 (free delivery). The admin can change it later.
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('shop_name',             'AM Tuff Tiles'),
    ('shop_email',            'tufftilesam@gmail.com'),
    ('shop_phone',            ''),
    ('shop_whatsapp',         ''),
    ('shop_address',          ''),
    ('delivery_charge_paisa', '0');
