<?php
$title = 'Network Map - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Network Map</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="zoomIn">
                <i class="fa fa-search-plus"></i>
            </button>
            <button type="button" class="btn btn-primary" id="zoomOut">
                <i class="fa fa-search-minus"></i>
            </button>
            <button type="button" class="btn btn-primary" id="resetZoom">
                <i class="fa fa-compress-arrows-alt"></i> Reset
            </button>
            <button type="button" class="btn btn-success" id="exportMap">
                <i class="fa fa-download"></i> Export
            </button>
        </div>
    </div>
</div>

<!-- Network Map Container -->
<div class="card">
    <div class="card-body">
        <!-- Map Controls -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="showOLTs" checked>
                    <label class="form-check-label" for="showOLTs">OLTs</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="showLCPs" checked>
                    <label class="form-check-label" for="showLCPs">LCPs</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="showNAPs" checked>
                    <label class="form-check-label" for="showNAPs">NAPs</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="showONUs">
                    <label class="form-check-label" for="showONUs">ONUs</label>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="layoutType">
                    <option value="hierarchical">Hierarchical Layout</option>
                    <option value="force">Force-Directed Layout</option>
                    <option value="circular">Circular Layout</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchNode" placeholder="Search nodes...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Map Canvas -->
        <div id="networkMap" style="height: 700px; border: 1px solid #ddd;"></div>

        <!-- Legend -->
        <div class="mt-3">
            <h6>Legend:</h6>
            <div class="d-flex gap-3">
                <div>
                    <i class="fa fa-server text-primary"></i> OLT
                </div>
                <div>
                    <i class="fa fa-box text-success"></i> LCP
                </div>
                <div>
                    <i class="fa fa-network-wired text-warning"></i> NAP
                </div>
                <div>
                    <i class="fa fa-laptop-house text-info"></i> ONU
                </div>
                <div>
                    <span class="badge bg-success">Active</span>
                </div>
                <div>
                    <span class="badge bg-warning">Warning</span>
                </div>
                <div>
                    <span class="badge bg-danger">Error</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Node Details Modal -->
<div class="modal fade" id="nodeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Node Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="nodeDetails"></div>
            </div>
        </div>
    </div>
</div>

<!-- Load vis.js -->
<link href="https://unpkg.com/vis-network/dist/vis-network.min.css" rel="stylesheet">
<script src="https://unpkg.com/vis-network/dist/vis-network.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Network data
    const nodes = new vis.DataSet(<?= json_encode(array_merge(
        // OLTs
        array_map(function($olt) {
            return [
                'id' => 'olt_' . $olt['id'],
                'label' => $olt['name'],
                'title' => "OLT: {$olt['name']}\nIP: {$olt['ip_address']}\nStatus: {$olt['status']}",
                'group' => 'olt',
                'status' => $olt['status']
            ];
        }, $olts),
        // LCPs
        array_map(function($lcp) {
            return [
                'id' => 'lcp_' . $lcp['id'],
                'label' => $lcp['name'],
                'title' => "LCP: {$lcp['name']}\nLocation: {$lcp['location']}",
                'group' => 'lcp',
                'status' => $lcp['status']
            ];
        }, $lcps),
        // NAPs
        array_map(function($nap) {
            return [
                'id' => 'nap_' . $nap['id'],
                'label' => $nap['name'],
                'title' => "NAP: {$nap['name']}\nLocation: {$nap['location']}",
                'group' => 'nap',
                'status' => $nap['status']
            ];
        }, $naps)
    )) ?>);

    // Create edges between nodes
    const edges = new vis.DataSet(<?= json_encode(array_merge(
        // OLT to LCP connections
        array_map(function($lcp) {
            return [
                'from' => 'olt_' . $lcp['olt_id'],
                'to' => 'lcp_' . $lcp['id']
            ];
        }, $lcps),
        // LCP to NAP connections
        array_map(function($nap) {
            return [
                'from' => 'lcp_' . $nap['lcp_id'],
                'to' => 'nap_' . $nap['id']
            ];
        }, $naps)
    )) ?>);

    // Network configuration
    const container = document.getElementById('networkMap');
    const data = {
        nodes: nodes,
        edges: edges
    };
    const options = {
        nodes: {
            shape: 'dot',
            size: 20,
            font: {
                size: 14
            }
        },
        edges: {
            width: 2,
            smooth: {
                type: 'continuous'
            }
        },
        groups: {
            olt: {
                color: { background: '#007bff', border: '#0056b3' },
                shape: 'square'
            },
            lcp: {
                color: { background: '#28a745', border: '#1e7e34' },
                shape: 'diamond'
            },
            nap: {
                color: { background: '#ffc107', border: '#d39e00' },
                shape: 'triangle'
            }
        },
        physics: {
            enabled: true,
            hierarchicalRepulsion: {
                centralGravity: 0.0,
                springLength: 200,
                springConstant: 0.01,
                nodeDistance: 150
            },
            solver: 'hierarchicalRepulsion'
        },
        layout: {
            hierarchical: {
                enabled: true,
                direction: 'UD',
                sortMethod: 'directed',
                levelSeparation: 150
            }
        }
    };

    // Create network
    const network = new vis.Network(container, data, options);

    // Node click handler
    network.on('click', function(params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = nodes.get(nodeId);
            
            // Fetch node details
            fetch(`/admin/network/node-details/${nodeId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('nodeDetails').innerHTML = data.html;
                    new bootstrap.Modal(document.getElementById('nodeDetailsModal')).show();
                });
        }
    });

    // Zoom controls
    document.getElementById('zoomIn').addEventListener('click', function() {
        network.moveTo({
            scale: network.getScale() * 1.2
        });
    });

    document.getElementById('zoomOut').addEventListener('click', function() {
        network.moveTo({
            scale: network.getScale() * 0.8
        });
    });

    document.getElementById('resetZoom').addEventListener('click', function() {
        network.fit();
    });

    // Layout type handler
    document.getElementById('layoutType').addEventListener('change', function() {
        const layout = this.value;
        if (layout === 'hierarchical') {
            network.setOptions({
                layout: {
                    hierarchical: {
                        enabled: true,
                        direction: 'UD',
                        sortMethod: 'directed'
                    }
                }
            });
        } else if (layout === 'force') {
            network.setOptions({
                layout: {
                    hierarchical: {
                        enabled: false
                    }
                },
                physics: {
                    enabled: true,
                    barnesHut: {
                        gravitationalConstant: -2000,
                        centralGravity: 0.3,
                        springLength: 200
                    }
                }
            });
        } else if (layout === 'circular') {
            network.setOptions({
                layout: {
                    hierarchical: {
                        enabled: false
                    }
                },
                physics: {
                    enabled: true,
                    barnesHut: {
                        gravitationalConstant: -10000,
                        centralGravity: 0.5,
                        springLength: 200
                    }
                }
            });
        }
    });

    // Node visibility toggles
    document.getElementById('showOLTs').addEventListener('change', function() {
        const visible = this.checked;
        nodes.forEach(node => {
            if (node.group === 'olt') {
                nodes.update({ id: node.id, hidden: !visible });
            }
        });
    });

    document.getElementById('showLCPs').addEventListener('change', function() {
        const visible = this.checked;
        nodes.forEach(node => {
            if (node.group === 'lcp') {
                nodes.update({ id: node.id, hidden: !visible });
            }
        });
    });

    document.getElementById('showNAPs').addEventListener('change', function() {
        const visible = this.checked;
        nodes.forEach(node => {
            if (node.group === 'nap') {
                nodes.update({ id: node.id, hidden: !visible });
            }
        });
    });

    // Search functionality
    document.getElementById('searchNode').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        nodes.forEach(node => {
            const matches = node.label.toLowerCase().includes(searchTerm);
            nodes.update({ id: node.id, color: matches ? { highlight: '#ff0000' } : null });
        });
    });

    document.getElementById('clearSearch').addEventListener('click', function() {
        document.getElementById('searchNode').value = '';
        nodes.forEach(node => {
            nodes.update({ id: node.id, color: null });
        });
    });

    // Export map
    document.getElementById('exportMap').addEventListener('click', function() {
        const canvas = container.querySelector('canvas');
        const image = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'network-map.png';
        link.href = image;
        link.click();
    });
});
</script>
