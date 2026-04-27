const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// The face icon replacement worked on the index! 
// Let's ensure the float button WhatsApp is there.
if (!index.includes('Botão Flutuante WhatsApp Elegante') && !index.includes('Botão Flutuante WhatsApp Intuitivo')) {
    // Should be there, but let's make sure it's the intuitive one
    // wait, we reverted to base, so maybe we need to apply the floating button again
}

