// Check if a plugin is installed
const isPluginInstalled = async (pluginSlug) => {
    const checkUrl = ajax_object.ajax_url;
    const data = new FormData();
    data.append('action', 'custom_plugin_installer_check_installed');
    data.append('slug', pluginSlug);

    try {
        const response = await fetch(checkUrl, {
            method: 'POST',
            body: data,
        });
        const result = await response.json();
        return result.installed;
    } catch (error) {
        console.error('Error:', error);
        return false;
    }
};

const updateButtonState = async (installButton, plugin, pluginPath) => {
    if (await isPluginInstalled(plugin.slug)) {
        if (await isPluginActive(pluginPath)) {
            installButton.textContent = 'Installed';
            installButton.disabled = true;
        } else {
            installButton.textContent = 'Activate';
            installButton.className = 'activate-button';
        }
    } else {
        installButton.textContent = 'Install';
        installButton.className = 'install-button';
    }
};

// Create plugin card
const createPluginCard = async (plugin) => {
    const card = document.createElement('div');
    card.className = 'plugin-card';

    const title = document.createElement('h3');
    title.textContent = plugin.name;

    const description = document.createElement('p');
    description.textContent = plugin.description;

    const installButton = document.createElement('button');
    installButton.addEventListener('click', () => installPlugin(plugin, installButton));
    installButton.textContent = 'Install';

    const pluginPath = `${plugin.slug}/${plugin.main_file}`;
    card.append(title, description, installButton);

    await updateButtonState(installButton, plugin, pluginPath);
    return card;
};

// Custom notification function
const showNotification = (message, type = 'info') => {
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
};

const installPlugin = async (plugin, installButton) => {
    installButton.disabled = true;
    installButton.textContent = 'Installing...';

    const installUrl = ajax_object.ajax_url;
    const data = new FormData();
    data.append('action', 'custom_plugin_installer_install');
    data.append('source', plugin.source);
    data.append('slug', plugin.slug);

    if (plugin.source === 'directory') {
        data.append('file', plugin.file);
    }

    try {
        const response = await fetch(installUrl, {
            method: 'POST',
            body: data,
        });
        const result = await response.json();

        if (result.success) {
            installButton.textContent = 'Installed';
        } else {
            installButton.textContent = 'Install';
            installButton.disabled = false;
            if (result.data && result.data.message) {
                showNotification(result.data.message, 'error');
            } else {
                alert(`Installation failed: ${JSON.stringify(result)}`);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        installButton.textContent = 'Install';
        installButton.disabled = false;
        alert(`Installation failed: ${error}`);
    }
};

const isPluginActive = async (pluginPath) => {
    const checkUrl = ajax_object.ajax_url;
    const data = new FormData();
    data.append('action', 'custom_plugin_installer_is_active');
    data.append('plugin_path', pluginPath);
    try {
        const response = await fetch(checkUrl, {
            method: 'POST',
            body: data,
        });
        const result = await response.json();
        return result.data.active;
    } catch (error) {
        console.error('Error:', error);
        return false;
    }
};

// Define sample plugin data
const samplePlugins = [
    {
        name: 'WooCommerce',
        slug: 'woocommerce',
        main_file: 'woocommerce.php',
        source: 'repository',
        description: 'WooCommerce is a flexible, open-source eCommerce solution built on WordPress.',
    },
    {
        name: 'Wordfence Security',
        slug: 'wordfence',
        main_file: 'wordfence.php',
        source: 'repository',
        description: 'Secure your website with the most comprehensive WordPress security plugin. Firewall, malware scan, blocking, live traffic, login security & more.',
    },
    {
        name: 'Contact Form 7',
        slug: 'contact-form-7',
        main_file: 'wp-contact-form-7.php',
        source: 'repository',
        description: 'Just another contact form plugin. Simple but flexible.',
    },
    {
        name: 'Jet Engine',
        slug: 'jet-engine',
        main_file: 'jet-engine.php',
        source: 'directory',
        file: 'jet-engine.zip',
        description: 'This is a custom plugin stored in a local directory.',
    },
];

// Add sample plugin cards to the container
const pluginCardContainer = document.getElementById('plugin-card-container');
Promise.all(
    samplePlugins.map(async (plugin) => {
        return await createPluginCard(plugin);
    })
).then((cards) => {
    cards.forEach((card) => {
        pluginCardContainer.appendChild(card);
    });
});