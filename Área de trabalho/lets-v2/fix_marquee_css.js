const fs = require('fs');
let index = fs.readFileSync('index.html', 'utf8');

// The original marquee animation had a CSS class name `animate-marquee`. 
// We need to make sure the CSS keyframes exist and are working correctly.
// Let's check for `.animate-marquee` and add it if missing, or adjust its speed to prevent it from "freezing".

const marqueeCSS = `
        .animate-marquee {
            display: flex;
            width: max-content;
            animation: marquee 30s linear infinite;
        }
        .animate-marquee:hover {
            animation-play-state: paused;
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
`;

if(!index.includes('@keyframes marquee')) {
    index = index.replace('</style>', marqueeCSS + '\n    </style>');
    fs.writeFileSync('index.html', index);
}

