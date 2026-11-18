async function apiPedidos(payload) {
    try {
        const r = await fetch('pedidos_api.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await r.json();
        if (!data.ok && data.msg && data.msg.toLowerCase().includes('sessão expirada')) {
            showToast(data.msg, 'error');
            setTimeout(() => { window.location.href = 'index.php'; }, 1500);
        }
        return data;
    } catch (e) {
        return { ok: false, msg: 'Falha de comunicação com o servidor.' };
    }
}


setInterval(() => {
    fetch('ping_sessao.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => {
            if (!d.ativo) {
                window.location.href = 'index.php';
            }
        })
        .catch(() => {});
}, 300000);

function recarregarComMsg(msg, tipo, destino) {
    try { sessionStorage.setItem('flashMsg', JSON.stringify({ msg, tipo })); } catch (e) {}
    window.location.href = destino;
}

function mostrarFlash() {
    let f = null;
    try { f = sessionStorage.getItem('flashMsg'); sessionStorage.removeItem('flashMsg'); } catch (e) {}
    if (!f) return;
    try {
        const { msg, tipo } = JSON.parse(f);
        setTimeout(() => showToast(msg, tipo), 400);
    } catch (e) {}
}
document.addEventListener('DOMContentLoaded', mostrarFlash);

function resolverIdItem(item) {
    if (item.itemId) return item.itemId;
    const card = [...document.querySelectorAll('.item-card')].find(c => c.getAttribute('data-name') === item.name);
    return card ? card.getAttribute('data-id') : null;
}