const fs = require('fs');
let como = fs.readFileSync('como-funciona.html', 'utf8');

como = como.replace(/bg-\[#25D366\] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-lg hover:shadow-xl w-full sm:w-auto/g, 
"bg-[#25D366] text-white px-8 py-4 rounded-full font-bold text-base md:text-lg hover:-translate-y-1 active:scale-95 transition-all duration-300 shadow-[0_10px_20px_rgba(37,211,102,0.3)] hover:shadow-[0_15px_30px_rgba(37,211,102,0.5)] border border-[#20b858] w-full sm:w-auto");

fs.writeFileSync('como-funciona.html', como);

