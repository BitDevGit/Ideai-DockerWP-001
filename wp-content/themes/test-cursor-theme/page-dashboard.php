<?php
/**
 * Template Name: Nested Sites Dashboard
 * 
 * Dashboard page template that displays all nested sites in iframes
 * This is served from WordPress so it's same-origin and iframes will work
 */

get_header();
?>
<style>
.dashboard-container { max-width: 100%; margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
.dashboard-header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.dashboard-header h1 { margin-bottom: 15px; color: #333; }
.stats { display: flex; gap: 20px; color: #666; font-size: 14px; margin-top: 15px; }
.sites-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px; }
.site-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; }
.site-card-header { padding: 15px; background: #0073aa; color: white; font-weight: 600; }
.site-card-header .path { font-size: 14px; opacity: 0.9; margin-top: 5px; }
.site-iframe-container { position: relative; width: 100%; height: 600px; border: none; overflow: hidden; background: #f0f0f0; }
.site-iframe { width: 100%; height: 100%; border: none; background: #fff; }
.iframe-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #666; font-size: 14px; }
.iframe-error { padding: 20px; background: #fee; color: #c33; text-align: center; }
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>🌳 Nested Sites Dashboard</h1>
        <div class="stats" id="stats">
            <span>Loading sites...</span>
        </div>
    </div>
    
    <div class="sites-grid" id="sites-grid">
        <div style="padding: 40px; text-align: center; color: #666;">Loading sites...</div>
    </div>
</div>

<script>
const sites = <?php
require_once ABSPATH . 'wp-content/mu-plugins/ideai.wp.plugin.platform/includes/nested-tree.php';
use Ideai\Wp\Platform\NestedTree;
global $wpdb;
$nested_table = NestedTree\table_name();
$sites_data = $wpdb->get_results($wpdb->prepare(
    'SELECT blog_id, path FROM ' . $nested_table . ' WHERE network_id=%d ORDER BY path ASC',
    1
), ARRAY_A);

$output = array();
foreach ($sites_data as $site) {
    switch_to_blog($site['blog_id']);
    $name = get_option('blogname');
    $depth = NestedTree\get_site_depth($site['path']);
    $url = get_option('home');
    restore_current_blog();
    
    $output[] = array(
        'blog_id' => (int) $site['blog_id'],
        'path' => $site['path'],
        'name' => $name,
        'level' => $depth,
        'url' => $url
    );
}
echo json_encode($output, JSON_PRETTY_PRINT);
?>;

function renderSites() {
    const grid = document.getElementById('sites-grid');
    const stats = document.getElementById('stats');
    
    if (sites.length === 0) {
        grid.innerHTML = '<div class="iframe-error">No sites found</div>';
        return;
    }
    
    const grouped = {
        level1: sites.filter(s => s.level === 1).length,
        level2: sites.filter(s => s.level === 2).length,
        level3: sites.filter(s => s.level === 3).length
    };
    
    stats.innerHTML = `
        <span><strong>${sites.length}</strong> total sites</span>
        <span><strong>${grouped.level1}</strong> parent sites</span>
        <span><strong>${grouped.level2}</strong> child sites</span>
        <span><strong>${grouped.level3}</strong> grandchild sites</span>
    `;
    
    grid.innerHTML = '';
    sites.sort((a, b) => a.path.localeCompare(b.path));
    
    const parentGroups = {};
    sites.forEach(site => {
        const parts = site.path.split('/').filter(p => p);
        const parent = parts.length > 0 ? parts[0] : 'root';
        if (!parentGroups[parent]) {
            parentGroups[parent] = [];
        }
        parentGroups[parent].push(site);
    });
    
    Object.keys(parentGroups).sort().forEach(parent => {
        parentGroups[parent].forEach(site => {
            const card = createSiteCard(site);
            grid.appendChild(card);
        });
    });
}

function createSiteCard(site) {
    const card = document.createElement('div');
    card.className = `site-card level-${site.level}`;
    
    const url = site.url || (window.location.origin + site.path);
    const levelBadge = site.level === 1 ? '🏠' : site.level === 2 ? '📁' : '📄';
    const containerId = `iframe-container-${site.blog_id}`;
    const iframeId = `iframe-${site.blog_id}`;
    
    card.innerHTML = `
        <div class="site-card-header">
            <div>${levelBadge} ${site.name}</div>
            <div class="path">${site.path} (blog_id: ${site.blog_id})</div>
        </div>
        <div class="site-iframe-container" id="${containerId}">
            <div class="iframe-loading">Loading ${site.path}...</div>
            <iframe 
                id="${iframeId}"
                class="site-iframe" 
                src="${url}" 
                title="${site.name}"
                loading="lazy"
                onload="handleIframeLoad('${iframeId}', '${containerId}')"
                onerror="handleIframeError('${containerId}', '${url}')"
            ></iframe>
        </div>
    `;
    
    setTimeout(() => {
        const iframe = document.getElementById(iframeId);
        if (iframe) {
            try {
                if (!iframe.contentWindow || iframe.contentWindow.location.href === 'about:blank') {
                    handleIframeError(containerId, url);
                }
            } catch (e) {
                // Cross-origin check failed, but iframe might still be loading
            }
        }
    }, 10000);
    
    return card;
}

function handleIframeLoad(iframeId, containerId) {
    const container = document.getElementById(containerId);
    const loading = container.querySelector('.iframe-loading');
    if (loading) {
        loading.style.display = 'none';
    }
}

function handleIframeError(containerId, url) {
    const container = document.getElementById(containerId);
    if (container && container.querySelector('.iframe-loading')) {
        container.innerHTML = `
            <div class="iframe-error">
                <p><strong>⚠️ Could not load iframe</strong></p>
                <p style="font-size: 12px; margin-top: 10px;">
                    <a href="${url}" target="_blank" style="color: #0073aa;">Open in new tab →</a>
                </p>
            </div>
        `;
    }
}

// Render on page load
document.addEventListener('DOMContentLoaded', renderSites);
</script>

<?php
get_footer();

