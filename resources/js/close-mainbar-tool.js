il = window.il || {};
il.EDUTIEK = il.EDUTIEK || {};
il.EDUTIEK.closeTools = tools => {
    document.cookie.split(';').forEach(cookie => {
        const entry = cookie.trim().split('=');
        let value;
        try {
            value = JSON.parse(entry[1]);
        } catch (error) {
            return;
        }
        if (Reflect.has(value || {}, 'tools_engaged')) {
            value.tools_engaged = false;
            value.any_entry_engaged = false;
            value.known_tools = tools;
            tools.forEach((tool, i) => {
                // tools are "compressed" as an array of [<removable>, <engaged>, <hidden>, <position>]
                // We only whant to set <engaged> to 0, but if the tool hasn't been set, we need to add it.
                value.tools[tool] = value.tools[tool] || [0, 0, 0, 'T:' + i];
                value.tools[tool][1] = 0;
            });
            document.cookie = entry[0] + '=' + JSON.stringify(value);
        }
    });
};
