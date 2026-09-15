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

let sessionJobs = [];

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const source = document.querySelector('input[name="source"]:checked').value;
    const query = document.getElementById('query').value.trim();
    const location = document.getElementById('location').value.trim();
    const quantity = parseInt(quantitySlider.value);
    const expand = document.getElementById('expand').checked;
    const metropolitan = document.getElementById('metropolitan').checked;

    if (!query) return;

    setLoading(true);
    completionSection.classList.add('hidden');

    try {
        const data = await api('/api/crawl', {
            method: 'POST',
            body: JSON.stringify({ source, query, location, quantity, expand, metropolitan }),
        });

        currentJobId = data.job_id;
        progressSection.classList.remove('hidden');
        
        // Add to session history
        sessionJobs.unshift({
            uuid: data.job_id,
            query: query,
            location: location,
            total: quantity,
            progress: 0,
            status: 'running'
        });
        renderSessionHistory();
        
        startPolling(quantity);
    } catch (err) {
        showToast(err.message, 'error');
        setLoading(false);
    }
});

function renderSessionHistory() {
    const container = document.getElementById('sessionHistory');
    const countEl = document.getElementById('sessionJobCount');
    if (!container) return;

    countEl.textContent = sessionJobs.length;

    if (sessionJobs.length === 0) {
        container.innerHTML = '<p class="text-center text-[11px] text-zinc-700 py-4 italic">Nenhuma busca iniciada ainda.</p>';
        return;
    }

    container.innerHTML = sessionJobs.map(job => {
        const pct = Math.min(100, Math.round((job.progress / job.total) * 100));
        const isDone = job.status === 'completed';
        const isError = job.status === 'error';

        return `
            <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 flex flex-col gap-2 transition-all">
                <div class="flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-[12px] font-bold text-zinc-100 truncate">${escapeHtml(job.query)}</p>
                        <p class="text-[9px] text-zinc-600 uppercase tracking-tighter">${escapeHtml(job.location || 'Brasil')}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-[10px] font-black ${isDone ? 'text-emerald-400' : isError ? 'text-red-400' : 'text-blue-400'} uppercase tracking-widest">${job.status}</span>
                        <span class="text-[9px] text-zinc-500 font-mono">${job.progress}/${job.total}</span>
                    </div>
                </div>
                <div class="h-1 w-full bg-zinc-900 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 transition-all duration-500" style="width: ${pct}%"></div>
                </div>
            </div>
        `;
    }).join('');
    lucide.createIcons();
}

function startPolling(total) {
    pollInterval = setInterval(async () => {
        try {
            const data = await api(`/api/crawl/status/${currentJobId}`);
            const pct = Math.min((data.progress / total) * 100, 100);

            // Update state
            const job = sessionJobs.find(j => j.uuid === currentJobId);
            if (job) {
                job.progress = data.progress;
                job.status = data.status;
                renderSessionHistory();
            }

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
