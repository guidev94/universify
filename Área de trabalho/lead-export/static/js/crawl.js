const form = document.getElementById('crawlForm');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const btnLoading = document.getElementById('btnLoading');
const progressSection = document.getElementById('progressSection');
const completionSection = document.getElementById('completionSection');
const quantitySlider = document.getElementById('quantity');
const quantityValue = document.getElementById('quantityValue');

let currentJobId = null;
let pollInterval = null;

quantitySlider.addEventListener('input', () => {
    quantityValue.textContent = quantitySlider.value;
});

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const source = document.querySelector('input[name="source"]:checked').value;
    const query = document.getElementById('query').value.trim();
    const location = document.getElementById('location').value.trim();
    const quantity = parseInt(quantitySlider.value);
    const expand = document.getElementById('expand').checked;

    if (!query) return;

    setLoading(true);
    completionSection.classList.add('hidden');

    try {
        const data = await api('/api/crawl', {
            method: 'POST',
            body: JSON.stringify({ source, query, location, quantity, expand }),
        });

        currentJobId = data.job_id;
        progressSection.classList.remove('hidden');
        startPolling(quantity);
    } catch (err) {
        showToast(err.message, 'error');
        setLoading(false);
    }
});

function startPolling(total) {
    pollInterval = setInterval(async () => {
        try {
            const data = await api(`/api/crawl/status/${currentJobId}`);
            const pct = Math.min((data.progress / total) * 100, 100);

            document.getElementById('progressBar').style.width = pct + '%';
            document.getElementById('progressText').textContent = `${data.progress} / ${total}`;
            document.getElementById('progressStatus').textContent = `Buscando leads qualificados... ${Math.round(pct)}%`;

            if (data.status === 'completed') {
                clearInterval(pollInterval);
                progressSection.classList.add('hidden');
                completionSection.classList.remove('hidden');
                document.getElementById('foundCount').textContent = data.progress;
                setLoading(false);
                showToast(`${data.progress} leads extraídos com sucesso!`, 'success');
            } else if (data.status === 'error') {
                clearInterval(pollInterval);
                document.getElementById('progressStatus').textContent = 'Erro na extração';
                setLoading(false);
                showToast('Erro durante a extração', 'error');
            }
        } catch {
            clearInterval(pollInterval);
            setLoading(false);
        }
    }, 2000);
}

function setLoading(loading) {
    submitBtn.disabled = loading;
    btnText.classList.toggle('hidden', loading);
    btnLoading.classList.toggle('hidden', !loading);
    if (loading) btnLoading.classList.add('flex');
    else btnLoading.classList.remove('flex');
}

async function loadSuggestions() {
    try {
        const data = await api('/api/keywords/suggestions');
        const container = document.getElementById('keywordSuggestions');

        if (!data.keywords || data.keywords.length === 0) {
            container.innerHTML = '<span class="text-xs text-zinc-600">Nenhuma busca anterior</span>';
            return;
        }

        container.innerHTML = data.keywords.map(kw => `
            <button type="button" onclick="useSuggestion('${escapeHtml(kw.keyword)}', '${escapeHtml(kw.location)}')"
                class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs text-zinc-300 hover:bg-blue-500/10 hover:border-blue-500/30 hover:text-blue-400 transition-all">
                ${escapeHtml(kw.keyword)}${kw.location ? ' • ' + escapeHtml(kw.location) : ''}
                <span class="text-zinc-600 ml-1">(${kw.total_leads_found})</span>
            </button>
        `).join('');
    } catch (e) {}
}

function useSuggestion(keyword, location) {
    document.getElementById('query').value = keyword;
    document.getElementById('location').value = location;
}

loadSuggestions();
