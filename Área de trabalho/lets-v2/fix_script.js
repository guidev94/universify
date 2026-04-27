const fs = require('fs');

let index = fs.readFileSync('index.html', 'utf8');

// Remove updatePricingUI function and logic if it exists
index = index.replace(/function updatePricingUI\(\) \{[\s\S]*?btnAnual\.addEventListener\('click'[\s\S]*?\n/, '');

fs.writeFileSync('index.html', index);

