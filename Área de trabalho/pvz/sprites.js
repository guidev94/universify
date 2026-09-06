var container = document.getElementById('container');
const ASSET_BASE = 'https://cdn.jsdelivr.net/gh/Jasmine709/Plants-VS-Zombies-Game@main/images/';

function generalPosition(plant){
    var top = plant.offsetTop;
    var left = plant.offsetLeft;
    
    // Grid lúdico (simplificado para o mapa do marblexu)
    const startX = 140;
    const startY = 100;
    const cw = 82;
    const ch = 100;

    let col = Math.round((left - startX) / cw);
    let row = Math.round((top - startY) / ch);

    if (col < 0) col = 0; if (col > 8) col = 8;
    if (row < 0) row = 0; if (row > 4) row = 4;

    plant.style.left = startX + col * cw + 'px';
    plant.style.top = startY + row * ch + 'px';
    plant.route = row;
    plant.gx = col;
    plant.gy = row;
    
    return true;
}

function followMouse(plant){
    plant.style.position = 'absolute';
    plant.style.zIndex = '500';
    document.onmousemove = e => {
        const rect = container.getBoundingClientRect();
        plant.style.top = (e.clientY - rect.top - 40) + 'px';
        plant.style.left = (e.clientX - rect.left - 40) + 'px';
    }
}

function createNut(type, callback){
    var nut = document.createElement('img');
    nut.src = ASSET_BASE + 'Plants/WallNut/WallNut.gif';
    nut.score = 50;
    nut.blood = 40;
    followMouse(nut);
    document.onmousedown = e => {
        if(e.button == 0){
            document.onmousemove = null;
            document.onmousedown = null;
            generalPosition(nut);
            callback(nut);
        }
    }
    container.appendChild(nut);
    return nut;
}

function createPeashooter(type, callback) {
    var p = document.createElement('img');
    p.type = type;
    if(type == 1){
        p.src = ASSET_BASE + 'Plants/Peashooter/Peashooter.gif';
        p.score = 100;
    } else {
        p.src = ASSET_BASE + 'Plants/SunFlower/SunFlower.gif';
        p.score = 50;
    }
    p.blood = 10;
    followMouse(p);
    document.onmousedown = e => {
        if (e.button == 0) {
            document.onmousemove = null;
            document.onmousedown = null;
            generalPosition(p);
            callback(p);
        }
    }
    container.appendChild(p);
    return p;
}

function createSun(flower, code, callback){
    var sun = document.createElement('img');
    sun.src = ASSET_BASE + 'interface/Sun.gif';
    sun.style.position = 'absolute';
    sun.style.zIndex = '600';
    sun.style.cursor = 'pointer';
    sun.style.top = (parseInt(flower.style.top) - 20) + 'px';
    sun.style.left = flower.style.left;
    sun.onclick = () => callback(sun);
    
    let targetY = parseInt(sun.style.top) + 40;
    sun.step = () => {
        let curY = parseInt(sun.style.top);
        if(curY < targetY) sun.style.top = (curY + 2) + 'px';
    };
    container.appendChild(sun);
    return sun;
}

function createBullet(peashooter, code, callback) {
    var bullet = document.createElement('img');
    bullet.src = ASSET_BASE + 'Plants/PB00.gif';
    bullet.style.position = 'absolute';
    bullet.style.zIndex = '400';
    bullet.route = peashooter.route;
    bullet.style.top = (parseInt(peashooter.style.top) + 10) + 'px';
    bullet.style.left = (parseInt(peashooter.style.left) + 40) + 'px';
    
    bullet.step = () => {
        let left = parseInt(bullet.style.left);
        if (left < 900) {
            bullet.style.left = (left + 8) + 'px';
        } else {
            callback(bullet);
        }
    }
    container.appendChild(bullet);
    return bullet;
}

function createZombie(id, gameover) {
    var z = document.createElement('img');
    z.src = ASSET_BASE + 'Zombies/Zombie/Zombie.gif';
    z.style.position = 'absolute';
    z.style.zIndex = '1000';
    z.route = Math.floor(Math.random() * 5);
    z.style.top = (100 + z.route * 100 - 20) + 'px';
    z.style.left = '850px';
    z.blood = 10;
    z.speed = 0.5;
    
    z.step = () => {
        let left = parseFloat(z.style.left);
        z.style.left = (left - z.speed) + 'px';
        if (left < 50) gameover();
    }
    container.appendChild(z);
    return z;
}
