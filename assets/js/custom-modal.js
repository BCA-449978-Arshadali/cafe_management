// =============================================
// Droppers Café — Global Custom Confirm Modal
// Ek baar load hoga, poori website pe kaam karega
// =============================================

function showConfirm(message, onConfirm, options = {}) {
    // Agar pehle se modal hai toh remove karo
    const existing = document.getElementById('gcm-wrapper');
    if (existing) existing.remove();

    const icon    = options.icon    || '⚠️';
    const okText  = options.okText  || 'Yes, Confirm';
    const okClass = options.okClass || 'gcm-btn-ok';

    // Modal HTML
    const wrapper = document.createElement('div');
    wrapper.id = 'gcm-wrapper';
    wrapper.innerHTML = `
        <div class="gcm-overlay" id="gcmOverlay">
            <div class="gcm-box" id="gcmBox">
                <div class="gcm-icon">${icon}</div>
                <p class="gcm-msg">${message}</p>
                <div class="gcm-btns">
                    <button id="gcmOkBtn" class="${okClass}">${okText}</button>
                    <button id="gcmCancelBtn" class="gcm-btn-cancel">Cancel</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(wrapper);

    // OK button
    document.getElementById('gcmOkBtn').onclick = function () {
        wrapper.remove();
        if (typeof onConfirm === 'function') onConfirm();
    };

    // Cancel button
    document.getElementById('gcmCancelBtn').onclick = function () {
        wrapper.remove();
    };

    // Overlay click se close
    document.getElementById('gcmOverlay').onclick = function (e) {
        if (e.target === this) wrapper.remove();
    };

    // ESC key se close
    document.onkeydown = function (e) {
        if (e.key === 'Escape') wrapper.remove();
    };
}

// =============================================
// CSS — Sirf ek baar inject hoga
// =============================================
(function injectModalCSS() {
    if (document.getElementById('gcm-style')) return;
    const style = document.createElement('style');
    style.id = 'gcm-style';
    style.innerHTML = `
        .gcm-overlay {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.78);
            display: flex; align-items: center;
            justify-content: center; z-index: 999999;
            animation: gcmBgIn 0.2s ease;
        }
        @keyframes gcmBgIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .gcm-box {
            background: #1a1a1a;
            border: 1px solid #ff8c00;
            border-radius: 16px;
            padding: 35px 40px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 0 40px rgba(255, 140, 0, 0.3);
            animation: gcmBoxIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes gcmBoxIn {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .gcm-icon {
            font-size: 42px;
            margin-bottom: 14px;
            line-height: 1;
        }
        .gcm-msg {
            color: #d0d0d0;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 26px;
            margin-top: 0;
        }
        .gcm-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .gcm-btn-ok, .gcm-btn-danger {
            padding: 10px 26px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            min-width: 120px;
        }
        .gcm-btn-ok {
            background: #ff8c00;
            color: #fff;
        }
        .gcm-btn-ok:hover {
            background: #e07800;
            transform: translateY(-1px);
        }
        .gcm-btn-danger {
            background: #c0392b;
            color: #fff;
        }
        .gcm-btn-danger:hover {
            background: #a93226;
            transform: translateY(-1px);
        }
        .gcm-btn-cancel {
            padding: 10px 26px;
            background: transparent;
            color: #999;
            border: 1px solid #444;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            min-width: 100px;
        }
        .gcm-btn-cancel:hover {
            border-color: #ff8c00;
            color: #ff8c00;
        }
    `;
    document.head.appendChild(style);
})();
