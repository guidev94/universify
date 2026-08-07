const fs = require('fs');
const path = require('path');

export default function handler(req, res) {
  const { endpoint, q, cat, sku, limit = 12, offset = 0 } = req.query;
  const cacheDir = path.join(process.cwd(), 'api', 'cache_data');

  const getProp = (obj, prop) => {
    if (!obj) return '';
    const key = Object.keys(obj).find(k => k.toLowerCase() === prop.toLowerCase());
    return key ? obj[key] : '';
  };

  try {
    if (endpoint === 'categories') {
      const filePath = path.join(cacheDir, 'categories_list.json');
      if (fs.existsSync(filePath)) {
        const data = fs.readFileSync(filePath, 'utf8');
        return res.status(200).json(JSON.parse(data));
      }
      return res.status(200).json([]);
    }

    if (endpoint === 'all_products') {
      const filePath = path.join(cacheDir, 'products_all.json');
      if (fs.existsSync(filePath)) {
        const data = fs.readFileSync(filePath, 'utf8');
        return res.status(200).json(JSON.parse(data));
      }
      return res.status(200).json([]);
    }

    if (endpoint === 'product') {
      const filePath = path.join(cacheDir, `p_${sku}.json`);
      if (fs.existsSync(filePath)) {
        const data = fs.readFileSync(filePath, 'utf8');
        return res.status(200).json(JSON.parse(data));
      }
      return res.status(404).json({ error: 'Product not found' });
    }

    if (endpoint === 'search' || !endpoint) {
      const filePath = path.join(cacheDir, 'products_all.json');
      if (!fs.existsSync(filePath)) return res.status(200).json([]);

      const all = JSON.parse(fs.readFileSync(filePath, 'utf8'));
      const query = (q || '').toLowerCase();
      
      let filtered = all.filter(p => {
        const p_type = getProp(p, 'type');
        const p_sub = getProp(p, 'sub_type');
        const p_name = getProp(p, 'name');
        const p_ref = getProp(p, 'prod_reference');

        const matchCat = !cat || p_type === cat || p_sub === cat;
        const matchQuery = !query || 
                           p_name.toLowerCase().includes(query) || 
                           p_ref.toLowerCase().includes(query);

        return matchCat && matchQuery;
      });

      return res.status(200).json(filtered.slice(parseInt(offset), parseInt(offset) + parseInt(limit)));
    }

    return res.status(400).json({ error: 'Invalid endpoint' });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: 'Internal Server Error' });
  }
}
