function getSelectedTiers() {
    const checked = document.querySelectorAll('input[name="tier"]:checked');
    return Array.from(checked).map(cb => cb.value).join(',');
}

async function loadKeywords() {
    try {
        const data = await api('/api/keywords/suggestions');
        const select = document.getElementById('exportKeyword');
        if (data.keywords && data.keywords.length) {
            data.keywords.forEach(kw => {
                const opt = document.createElement('option');
                opt.value = kw.keyword;
                opt.textContent = `${kw.keyword} (${kw.total_leads_found} leads)`;
                select.appendChild(opt);
            });
        }
    } catch (e) {}
}

async function updatePreview() {
    const tier = getSelectedTiers();
    const status = document.getElementById('exportStatus').value;
    const keyword = document.getElementById('exportKeyword').value;

    try {
        const data = await api('/api/export/preview', {
            method: 'POST',
            body: JSON.stringify({ tier: tier || null, status: status || null, keyword: keyword || null }),
        });
        document.getElementById('previewCount').textContent = data.count.toLocaleString('pt-BR');

        const list = document.getElementById('previewList');
        if (data.preview && data.preview.length) {
            list.classList.remove('hidden');
            list.innerHTML = `<p class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wide mb-2">Prévia (${Math.min(5, data.count)} de ${data.count})</p>` +
                data.preview.map(l => `
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                        <div class="min-w-0 flex-1">
                            <p class="text-[12px] text-zinc-200 font-medium truncate">${escapeHtml(l.name)}</p>
                            <p class="text-[10px] text-zinc-500 truncate">${l.email || l.phone || '—'}</p>
                        </div>
                        <span class="text-[10px] font-bold ml-2 ${l.tier === 'hot' ? 'text-amber-400' : l.tier === 'warm' ? 'text-orange-400' : 'text-zinc-500'}">${l.score}</span>
                    </div>
                `).join('');
        } else {
            list.classList.add('hidden');
        }
    } catch (e) {
        document.getElementById('previewCount').textContent = '—';
    }
}

async function doExport() {
    const format = document.querySelector('input[name="format"]:checked').value;
    const tier = getSelectedTiers();
    const status = document.getElementById('exportStatus').value;
    const quantity = document.getElementById('exportQuantity').value;
    const keyword = document.getElementById('exportKeyword').value;

    const btn = document.getElementById('exportBtn');
    btn.disabled = true;
    btn.innerHTML = '<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Exportando...';

    try {
        const response = await fetch('/api/export', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCSRFToken(),
            },
            body: JSON.stringify({
                format,
                tier: tier || null,
                status: status || null,
                quantity: quantity ? parseInt(quantity) : null,
                keyword: keyword || null,
            }),
        });

        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.error || 'Erro ao exportar');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leads-${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        showToast('Exportação concluída!', 'success');
    } catch (e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="download" class="w-4.5 h-4.5 stroke-[2]"></i> Exportar Leads';
        lucide.createIcons();
    }
}

document.querySelectorAll('input[name="tier"]').forEach(cb => cb.addEventListener('change', updatePreview));
document.getElementById('exportStatus').addEventListener('change', updatePreview);
document.getElementById('exportKeyword').addEventListener('change', updatePreview);

loadKeywords();
updatePreview();
