const wrapper = document.querySelector('.galaxy-wrapper');
const sheets = Array.from(document.querySelectorAll('.hologram-sheet'));

let isDown = false;
let startX;
let activeIndex = 0;
let progressOffset = 0;

const CLOSED_WIDTH = 60; 
const GAP = 15;

function getActiveWidth() {
    const totalWidth = window.innerWidth;
    const padding = 40 * 2; 
    const closedTotalSpace = CLOSED_WIDTH * (sheets.length - 1);
    const gapsTotalSpace = GAP * (sheets.length - 1);
    return totalWidth - padding - closedTotalSpace - gapsTotalSpace;
}

// 🌟 GERÇEK ZAMANLI EŞZAMANLI OPAKLIK GEÇİŞ MOTORU (FADE IN/OUT) 🌟
function updateSheetsLayout(offset = 0) {
    if (window.innerWidth < 992) {
        sheets.forEach(s => {
            s.style.width = '';
            const content = s.querySelector('.sheet-content');
            if (content) { content.style.opacity = ''; content.style.transform = ''; content.style.visibility = ''; }
        });
        return;
    }

    const activeWidth = getActiveWidth();
    let virtualIndex = activeIndex + offset;
    
    // Sınırlandırma kilidi
    if (virtualIndex < 0) virtualIndex = 0;
    if (virtualIndex > sheets.length - 1) virtualIndex = sheets.length - 1;

    sheets.forEach((sheet, i) => {
        let distance = Math.abs(i - virtualIndex);
        let currentWidth = CLOSED_WIDTH;
        let opacity = 0;
        let translateX = 20;

        if (distance < 1) {
            // Fare hareket ettikçe genişlik ve opaklık milimetrik olarak hesaplanır
            currentWidth = CLOSED_WIDTH + (activeWidth - CLOSED_WIDTH) * (1 - distance);
            opacity = 1 - distance; // Sürükleme oranına göre opaklık formülü
            translateX = 20 * distance; // Hafifçe sağa kayma esnemesi
        }

        sheet.style.width = `${currentWidth}px`;
        
        // Kilitlenme anında aktiflik sınıfları
        if (i === activeIndex && offset === 0) {
            sheet.classList.add('active-sheet');
        } else if (offset === 0) {
            sheet.classList.remove('active-sheet');
        }

        // 🔥 İÇERİKLERİN ANİDEN DEĞİL, FARE HIZIYLA BİR BÜTÜN OLARAK SÖNÜP DOĞMASI
        const content = sheet.querySelector('.sheet-content');
        if (content) {
            content.style.opacity = opacity;
            content.style.transform = `translate3d(${translateX}px, 0, 0)`;
            content.style.visibility = opacity > 0.02 ? 'visible' : 'hidden';
        }
    });
}

// İlk Kurulum
window.addEventListener('resize', () => updateSheetsLayout());
updateSheetsLayout();

// Sol şerit tıklamaları (Manuel tıklandığında geçiş animasyonu ekler)
sheets.forEach((sheet, index) => {
    const tab = sheet.querySelector('.sheet-tab');
    tab.addEventListener('click', () => {
        sheets.forEach(s => {
            s.style.transition = 'width 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease';
            const content = s.querySelector('.sheet-content');
            if (content) content.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });
        
        activeIndex = index;
        updateSheetsLayout();
        
        setTimeout(() => {
            sheets.forEach(s => {
                s.style.transition = 'none';
                const content = s.querySelector('.sheet-content');
                if (content) content.style.transition = 'none';
            });
        }, 600);
    });
});

// SÜRÜKLEME OPERASYONU
wrapper.addEventListener('mousedown', (e) => {
    if (e.target.closest('.cosmic-form') || e.target.closest('.cosmic-search') || e.target.closest('.cosmic-card') || window.innerWidth < 992) return;
    isDown = true;
    startX = e.pageX;
    sheets.forEach(s => {
        s.style.transition = 'none';
        const content = s.querySelector('.sheet-content');
        if (content) content.style.transition = 'none';
    });
});

window.addEventListener('mouseup', (e) => {
    if (!isDown) return;
    isDown = false;
    
    const walk = e.pageX - startX;
    sheets.forEach(s => {
        s.style.transition = 'width 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease';
        const content = s.querySelector('.sheet-content');
        if (content) content.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    if (Math.abs(walk) > 130) {
        if (walk > 0 && activeIndex > 0) {
            activeIndex--;
        } else if (walk < 0 && activeIndex < sheets.length - 1) {
            activeIndex++;
        }
    }
    
    progressOffset = 0;
    updateSheetsLayout();
});

wrapper.addEventListener('mouseleave', () => { 
    if (isDown) { 
        isDown = false; 
        sheets.forEach(s => s.style.transition = 'width 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease');
        updateSheetsLayout(); 
    } 
});

wrapper.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    
    const x = e.pageX;
    const walk = x - startX;
    
    progressOffset = -walk / 450; 
    if (progressOffset > 1) progressOffset = 1;
    if (progressOffset < -1) progressOffset = -1;
    
    updateSheetsLayout(progressOffset);
});


// PARALAKTIK MOUSE IŞIK MOTORU
const flare = document.getElementById('quantumFlare');
let mouseX = -1000, mouseY = -1000;
let flareX = -1000, flareY = -1000;

window.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;
});

function updateFlare() {
    if (mouseX !== -1000) {
        flareX += (mouseX - flareX) * 0.085;
        flareY += (mouseY - flareY) * 0.085;
        if (flare) {
            flare.style.transform = `translate3d(${flareX - 300}px, ${flareY - 300}px, 0)`;
        }
    }
    requestAnimationFrame(updateFlare);
}
updateFlare();


// CANVAS KUANTUM PARÇACIK MOTORU
const canvas = document.getElementById('cosmicCanvas');
const ctx = canvas.getContext('2d');
let stars = [];
const starCount = window.innerWidth < 768 ? 25 : 55;

function resizeCanvas() {
    if (canvas) {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

class CosmicStar {
    constructor() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.size = Math.random() * 1.5 + 0.5;
        this.speedX = Math.random() * 0.2 - 0.1;
        this.speedY = Math.random() * -0.3 - 0.1;
        this.alpha = Math.random() * 0.5 + 0.1;
    }
    update() {
        this.x += this.speedX;
        this.y += this.speedY;
        if (this.y < 0) {
            this.y = canvas.height;
            this.x = Math.random() * canvas.width;
        }
    }
    draw() {
        ctx.fillStyle = `rgba(0, 255, 204, ${this.alpha})`;
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fill();
    }
}

for (let i = 0; i < starCount; i++) { stars.push(new CosmicStar()); }

function renderCosmos() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    stars.forEach(s => { s.update(); s.draw(); });
    requestAnimationFrame(renderCosmos);
}
renderCosmos();


// KARTLAR İÇİN 3D TILT EFEKTİ
const cosmicCards = document.querySelectorAll('[data-tilt]');
cosmicCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
        if (window.innerWidth < 992) return;
        const bound = card.getBoundingClientRect();
        const x = e.clientX - bound.left;
        const y = e.clientY - bound.top;
        const xc = bound.width / 2;
        const yc = bound.height / 2;
        const rotateX = (yc - y) / 30; 
        const rotateY = (x - xc) / 30;
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.015)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)`;
    });
});

function sitedeHileVarMi() {
    const hileInput = document.getElementById('hile_adi_sorgu').value.trim();
    const terminal = document.getElementById('sorgu-terminal');
    const btn = document.getElementById('btn-sorgula');

    if (!hileInput) {
        terminal.style.display = 'block';
        terminal.className = 'term-error';
        terminal.innerHTML = '>> [CORE_ERROR] Siber ağlarda aratmak için bir hile adı yazmalısın!';
        return;
    }

    // Butonu kilitle ve hırçın yükleme efektini başlat
    btn.disabled = true;
    btn.innerText = 'TARANIYOR...';
    terminal.style.display = 'block';
    terminal.className = 'term-loading';
    terminal.innerHTML = `>> [QUANTUM_SEARCH] Vayacheats siber veri tabanları taranıyor...<br>`;

    setTimeout(() => {
        terminal.innerHTML += `>> [BYPASS_CHECK] "${hileInput}" için aktif imza ve kernel durumu sorgulanıyor...<br>`;
    }, 600);

    // 1.5 saniye sonra htdocs'taki php motoruna sinyali çakıyoruz
    setTimeout(() => {
        fetch('hile_mevcut_mu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'arama_terimi=' + encodeURIComponent(hileInput)
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'SORGULA';

            if (data.mevcut === true) {
                terminal.className = 'term-success';
                let durumFisegi = data.durum === 'UNDETECTED' 
                    ? '<span style="color:#10b981; font-weight:bold; text-shadow: 0 0 10px #10b981;">[GÜNCEL / UNDETECTED]</span>' 
                    : '<span style="color:#eab308; font-weight:bold; text-shadow: 0 0 10px #eab308;">[BAKIMDA / DETECTED]</span>';

                terminal.innerHTML = `
                    >> [TARGET_FOUND] SİBER AĞDA HİLE ENJEKSİYONU TESPİT EDİLDİ!<br>
                    >> ==============================================================<br>
                    >> HİLE PROSESİ : <span style="color:#fff; font-weight:bold;">${data.hile_adi}</span><br>
                    >> GÜVENLİK     : ${durumFisegi}<br>
                    >> ENTEGRASYON  : ${data.koruma}<br>
                    >> ERİŞİM       : Yetkiniz var. Müşteri panelinden tek tıkla enjekte edebilirsiniz.
                `;
            } else {
                terminal.className = 'term-error';
                terminal.innerHTML = `
                    >> [SEARCH_FAILED] SİBER AĞ TARAMASI TAMAMLANDI<br>
                    >> ==============================================================<br>
                    >> SORGULANAN   : <span style="color:#fff; font-weight:bold;">"${hileInput}"</span><br>
                    >> DURUM        : Bu isimde aktif bir kuantum simülasyonu veya hile üssümüzde mevcut değil.<br>
                    >> TAVSİYE      : İsmi doğru yazdığınızdan emin olun veya Discord üzerinden bize bildirin!
                `;
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerText = 'SORGULA';
            terminal.className = 'term-error';
            terminal.innerHTML = '>> [CONN_LOST] Sunucu çekirdeği yanıt vermedi. hile_mevcut_mu.php dosyasını htdocs dizinine ekle!';
        });
    }, 1500);
}