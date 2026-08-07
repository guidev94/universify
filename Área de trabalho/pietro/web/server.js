const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 8000;

// Serve static files from the current directory
app.use(express.static(__dirname));

// Mock Vercel handler for local Node.js environment
const proxyHandler = (req, res) => {
  const { endpoint, q, cat, sku, limit = 12, offset = 0 } = req.query;
  const cacheDir = path.join(__dirname, 'api', 'cache_data');

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
        const p_type = getProp(p, 'type') || getProp(p, 'Type');
        const p_sub = getProp(p, 'sub_type') || getProp(p, 'SubType');
        const p_name = getProp(p, 'name') || getProp(p, 'Name');
        const p_ref = getProp(p, 'prod_reference') || getProp(p, 'ProdReference');

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
};

// Route for API
app.get('/api', proxyHandler);

app.listen(PORT, () => {
  console.log(`
🚀 M. Pietro Server is running!
----------------------------------
Local: http://localhost:${PORT}
Environment: Node.js (Full JS)
----------------------------------
  `);
});
