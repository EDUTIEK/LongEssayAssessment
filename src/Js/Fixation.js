/**
 * Handling fixation of settings
 */
export default class Fixation {

    constructor() {
        this.nodes = [];
    }

    addNode(id, visible) {
        const node = document.getElementById(id);
        if (node) {
            this.nodes.push(node);
            if (!visible) {
                node.classList.add('ilNoDisplay');
            }
        }
    }

    toggleNodes() {
       this.nodes.forEach(n => n.classList.toggle('ilNoDisplay'));
    }

    enableTemplate(target_url, value) {
        const target = new URL(window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + target_url);
        target.searchParams.set('enable', value);
        window.location = target;
    }

    updateGroup = function(target_url, group, value) {
        const target = new URL(window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + target_url);
        target.searchParams.set('group', group);
        target.searchParams.set('enable', value);
        window.location = target;
    }
}


