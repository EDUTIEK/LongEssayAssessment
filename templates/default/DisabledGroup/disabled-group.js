
window.il = window.il || {};
il.EDUTIEK = il.EDUTIEK || {};

il.EDUTIEK.disabledInputs = [];

il.EDUTIEK.disableInput = function(node, visible) {
    il.EDUTIEK.disabledInputs.push(node);
    if (!visible) {
        node.classList.add('ilNoDisplay');
    }
};

il.EDUTIEK.toggleDisabledInputs = function() {
    il.EDUTIEK.disabledInputs.forEach(n => n.classList.toggle('ilNoDisplay'));
};

il.EDUTIEK.enableTemplate = function(target_url, value) {
    const target = new URL(window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + target_url);
    target.searchParams.set('enable', value);
    window.location = target;
};

il.EDUTIEK.updateGroup = function(target_url, group, value) {
    const target = new URL(window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + target_url);
    target.searchParams.set('group', group);
    target.searchParams.set('enable', value);
    window.location = target;
};
