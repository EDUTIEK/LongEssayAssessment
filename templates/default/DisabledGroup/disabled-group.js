
window.il = window.il || {};
il.EDUTIEK = il.EDUTIEK || {};

il.EDUTIEK.disabledInputs = [];
il.EDUTIEK.disableInput = function(node) {
    il.EDUTIEK.disabledInputs.push(node);
    node.classList.add('ilNoDisplay');
};

il.EDUTIEK.toggleDisabledInputs = function() {
    il.EDUTIEK.disabledInputs.forEach(n => n.classList.toggle('ilNoDisplay'));
};
