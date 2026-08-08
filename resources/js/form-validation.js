const ERROR_CLASS = 'js-validation-error';
const ERROR_COLOR = '#dc2626';
const SUCCESS_COLOR = '#16a34a';
const styled = new Set();

function setup() {
    document.querySelectorAll('form').forEach((form) => {
        form.setAttribute('novalidate', '');
    });

    document.addEventListener('input', onFieldChange, true);
    document.addEventListener('change', onFieldChange, true);

    document.addEventListener('submit', handleSubmit, true);

    window.addEventListener('helena:clear-validation', clearAllErrors);

    // Alpine's x-show hides elements by setting style.display = 'none'. When any
    // element becomes hidden, wipe validation state inside it so errors never
    // survive a close/reopen cycle regardless of how the container was closed.
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type !== 'attributes' || mutation.attributeName !== 'style') continue;
            const el = mutation.target;
            if (el instanceof HTMLElement && el.style.display === 'none') {
                clearSubtreeErrors(el);
            }
        }
    });
    observer.observe(document.body, { subtree: true, attributes: true, attributeFilter: ['style'] });
}

function handleSubmit(event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.jsValidation === 'off') return;

    const seen = new Set();
    const invalid = Array.from(form.elements).filter((el) => {
        if (!el.willValidate || el.checkValidity()) return false;
        if (el.type === 'radio' || el.type === 'checkbox') {
            if (seen.has(el.name)) return false;
            seen.add(el.name);
        }
        return true;
    });

    if (invalid.length === 0) return;

    event.preventDefault();
    event.stopImmediatePropagation();

    invalid.forEach(showError);
    const focusTarget = invalid.find((el) => el.type !== 'radio' && el.type !== 'checkbox') || invalid[0];
    focusTarget.focus();
}

function onFieldChange(event) {
    const el = event.target;
    if (!(el instanceof Element) || !el.matches('input, select, textarea')) return;

    clearError(el);

    if (!el.willValidate) return;

    if (el.checkValidity()) {
        styled.add(el);
        el.style.borderColor = SUCCESS_COLOR;
    } else {
        showError(el);
    }
}

function showError(el) {
    clearError(el);

    const p = document.createElement('p');
    p.className = `${ERROR_CLASS} mt-1 text-xs text-red-600`;
    p.setAttribute('role', 'alert');
    p.textContent = messageFor(el);

    if (el.type === 'radio' || el.type === 'checkbox') {
        const group = el.closest('fieldset') || el.parentElement;
        if (group) group.append(p);
    } else {
        el.insertAdjacentElement('afterend', p);
    }

    el.setAttribute('aria-invalid', 'true');
    styled.add(el);
    el.style.borderColor = ERROR_COLOR;
}

function messageFor(el) {
    const v = el.validity;
    if (v.valueMissing) return 'This field is required.';
    if (v.typeMismatch && el.type === 'email') return 'Please enter a valid email address.';
    if (el.validationMessage) return el.validationMessage;
    return 'Please check this field.';
}

function clearError(el) {
    if (!(el instanceof Element)) return;

    let sibling = el.nextElementSibling;
    if (sibling && sibling.classList.contains(ERROR_CLASS)) sibling.remove();

    if (el.type === 'radio' || el.type === 'checkbox') {
        const group = el.closest('fieldset') || el.parentElement;
        if (group) group.querySelectorAll(`.${ERROR_CLASS}`).forEach((p) => p.remove());
    }

    const describedBy = el.getAttribute('aria-describedby');
    if (describedBy) {
        describedBy.split(/\s+/).forEach((id) => {
            const node = document.getElementById(id);
            if (node && node.tagName === 'P' && node.classList.contains('text-red-600')) {
                node.remove();
            }
        });
        el.removeAttribute('aria-describedby');
    }

    clearFieldStyle(el);
}

function clearFieldStyle(el) {
    el.setAttribute('aria-invalid', 'false');
    if (styled.delete(el)) {
        el.style.borderColor = '';
    }
}

function clearAllErrors() {
    document.querySelectorAll(`.${ERROR_CLASS}`).forEach((p) => p.remove());
    Array.from(styled).forEach(clearFieldStyle);
}

function clearSubtreeErrors(container) {
    container.querySelectorAll(`.${ERROR_CLASS}`).forEach((p) => p.remove());
    Array.from(styled).forEach((el) => {
        if (container.contains(el)) clearFieldStyle(el);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
} else {
    setup();
}
