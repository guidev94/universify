function getSelectedTiers() {
    const checked = document.querySelectorAll('input[name="tier"]:checked');
    return Array.from(checked).map(cb => cb.value).join(',');
}

async function updatePreview() {
    const tier = getSelectedTiers();
    const status = document.getElementById('exportStatus').value;

    try {
        const data = await api('/api/export/preview', {
            method: 'POST',
            body: JSON.stringify({ tier: tier || null, status: status || null }),
        });
        document.getElementById('previewCount').textContent = data.count.toLocaleString('pt-BR');
    } catch (e) {
        document.getElementById('previewCount').textContent = '—';
    }
}

async function doExport() {
    const format = document.querySelector('input[name="format"]:checked').value;
    const tier = getSelectedTiers();
    const status = document.getElementById('exportStatus').value;
    const quantity = document.getElementById('exportQuantity').value;

    const btn = document.getElementById('exportBtn');
    btn.disabled = true;
    btn.textContent = 'Exportando...';

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
        a.download = `leads.${format}`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

        showToast('Exportação concluída!', 'success');
    } catch (e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Exportar Leads';
    }
}

document.querySelectorAll('input[name="tier"]').forEach(cb => {
    cb.addEventListener('change', updatePreview);
});
document.getElementById('exportStatus').addEventListener('change', updatePreview);

updatePreview();
