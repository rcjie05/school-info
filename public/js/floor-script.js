// floor-script.js

// Don't access DOM elements immediately - wait for DOMContentLoaded
let canvas;
let ctx;

// Get role from PHP-passed variable
let currentRole = window.currentUserRole || "student";
let currentUser = currentRole;  // kept for compatibility with existing code
let canEdit = window.canEditRoutes || false;
let routeDrawMode = false;
let waypoints = [];
let startRoom = null;
let endRoom = null;
let savedRoutes = [];
let zoomLevel = 1;
let panX = 0;
let panY = 0;

// Pin state
let pinnedRoom = null;
let pinAnim    = 0;  // 0→1 drop-in
let pinRipple  = 0;  // 0→1 ripple
let pinRaf     = null;


// Helper: resolve the routes API path relative to the current page.
function getRoutesApiPath() {
    if (window.routesApiPath) return window.routesApiPath;
    const dir = window.location.pathname.replace(/\/[^/]*$/, '');
    const lastSegment = dir.split('/').filter(Boolean).pop() || '';
    return '../../api/shared/routes.php';
}

// API Integration Functions
async function loadRoutesFromAPI() {
    try {
        const apiPath = getRoutesApiPath();
        
        console.log('=== LOAD ROUTES FROM API ===');
        console.log('Loading routes from:', apiPath);
        console.log('Current role:', currentRole);
        console.log('Window.currentUserRole:', window.currentUserRole);
        
        const response = await fetch(apiPath, {
            credentials: 'include'
        });
        
        console.log('Response status:', response.status);
        console.log('Response OK:', response.ok);
        
        const data = await response.json();
        
        console.log('=== API RESPONSE ===');
        console.log('Full response:', data);
        console.log('Success:', data.success);
        console.log('Routes array:', data.routes);
        console.log('Routes count:', data.routes ? data.routes.length : 0);
        
        if (data.debug) {
            console.log('Debug info from server:', data.debug);
        }
        
        if (data.success) {
            savedRoutes = data.routes || [];
            console.log('Saved routes assigned:', savedRoutes.length, 'routes');
            console.log('savedRoutes variable:', savedRoutes);
            
            if (currentRole === 'admin') {
                console.log('Calling displaySavedRoutes (admin)');
                displaySavedRoutes();
            } else {
                console.log('Calling displayStudentRoutes (non-admin)');
                console.log('Current role is:', currentRole);
                displayStudentRoutes();
            }
            return savedRoutes;
        } else {
            console.error('API returned success: false', data);
        }
    } catch (error) {
        console.error('Error loading routes:', error);
        console.error('Error stack:', error.stack);
    }
    return [];
}

async function saveRouteToAPI(routeData) {
    try {
        const apiPath = getRoutesApiPath();
        
        console.log('Saving route to:', apiPath);
        console.log('Route data:', routeData);
        
        const response = await fetch(apiPath, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(routeData)
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', errorText);
            return { 
                success: false, 
                message: `HTTP ${response.status}: ${errorText.substring(0, 100)}` 
            };
        }
        
        const data = await response.json();
        console.log('Response data:', data);
        return data;
    } catch (error) {
        console.error('Error saving route:', error);
        return { success: false, message: `Network error: ${error.message}` };
    }
}

async function deleteRouteFromAPI(routeId) {
    try {
        const apiPath = getRoutesApiPath();
        
        const response = await fetch(apiPath, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: routeId })
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error deleting route:', error);
        return { success: false, message: 'Network error' };
    }
}

// Room definitions
let rooms = []; // Loaded dynamically from database

// ────────────────────────────────────────────────
// Load rooms from database API
// ────────────────────────────────────────────────
function getRoomsApiPath() {
    const lastSegment = window.location.pathname.split('/').filter(Boolean).pop();
    // admin/floorplan.php  -> ../php/api/admin/get_floorplan_rooms.php
    // hr/floorplan.php     -> ../php/api/admin/get_floorplan_rooms.php
    // php/floorplan.php    -> api/admin/get_floorplan_rooms.php
    return lastSegment === 'php' ? 'api/admin/get_floorplan_rooms.php' : '../php/api/admin/get_floorplan_rooms.php';
}

async function loadRoomsFromDB() {
    try {
        const res = await fetch(getRoomsApiPath(), { credentials: 'include' });
        const data = await res.json();
        if (data.success && data.rooms.length > 0) {
            rooms = data.rooms;
            console.log('Floor map: loaded', rooms.length, 'rooms from database');
        } else {
            console.warn('Floor map: no rooms in DB, using fallback hardcoded layout');
            rooms = getFallbackRooms();
        }
    } catch (e) {
        console.error('Floor map: failed to load rooms from DB, using fallback', e);
        rooms = getFallbackRooms();
    }
}

function getFallbackRooms() {
    return [
        { name: 'AVP Office',        x: 10,  y: 15,  width: 200, height: 95,  color: '#F4D03F', centerX: 110, centerY: 62  },
        { name: 'College Classroom', x: 210, y: 15,  width: 185, height: 95,  color: '#85C1E2', centerX: 302, centerY: 62  },
        { name: 'Computer Lab',      x: 395, y: 15,  width: 175, height: 95,  color: '#85C1E2', centerX: 482, centerY: 62  },
        { name: 'Clinic',            x: 570, y: 15,  width: 185, height: 95,  color: '#7DCEA0', centerX: 662, centerY: 62  },
        { name: 'BED Principal',     x: 10,  y: 155, width: 115, height: 105, color: '#F4D03F', centerX: 67,  centerY: 207 },
        { name: 'Vice President',    x: 125, y: 155, width: 115, height: 105, color: '#F4D03F', centerX: 182, centerY: 207 },
        { name: 'Registrar',         x: 10,  y: 260, width: 230, height: 165, color: '#F4D03F', centerX: 125, centerY: 342 },
        { name: 'Quadrangle',        x: 280, y: 200, width: 275, height: 225, color: '#F1948A', centerX: 417, centerY: 312 },
        { name: 'MIS Office',        x: 400, y: 155, width: 355, height: 45,  color: '#F4D03F', centerX: 577, centerY: 177 },
        { name: 'CR 5',              x: 755, y: 155, width: 130, height: 45,  color: '#7DCEA0', centerX: 820, centerY: 177 },
        { name: 'Marketing',         x: 555, y: 200, width: 100, height: 45,  color: '#F4D03F', centerX: 605, centerY: 222 },
        { name: 'CCTI',              x: 755, y: 200, width: 130, height: 45,  color: '#F4D03F', centerX: 820, centerY: 222 },
        { name: 'BSBA Office',       x: 555, y: 245, width: 100, height: 55,  color: '#F4D03F', centerX: 605, centerY: 272 },
        { name: 'Guidance',          x: 755, y: 245, width: 130, height: 55,  color: '#F4D03F', centerX: 820, centerY: 272 },
        { name: 'Playgroup',         x: 585, y: 335, width: 135, height: 45,  color: '#85C1E2', centerX: 652, centerY: 357 },
        { name: 'CR 1',              x: 755, y: 335, width: 130, height: 45,  color: '#7DCEA0', centerX: 820, centerY: 357 },
        { name: 'Lounging Room',     x: 585, y: 380, width: 135, height: 45,  color: '#7DCEA0', centerX: 652, centerY: 402 },
        { name: 'CR 2',              x: 755, y: 380, width: 130, height: 45,  color: '#7DCEA0', centerX: 820, centerY: 402 },
        { name: 'CR 3',              x: 755, y: 425, width: 130, height: 45,  color: '#7DCEA0', centerX: 820, centerY: 447 },
        { name: 'CR 4',              x: 755, y: 470, width: 130, height: 35,  color: '#7DCEA0', centerX: 820, centerY: 487 },
        { name: 'Banko Maximo',      x: 10,  y: 495, width: 115, height: 115, color: '#7DCEA0', centerX: 67,  centerY: 552 },
        { name: 'HR',                x: 125, y: 495, width: 115, height: 115, color: '#F4D03F', centerX: 182, centerY: 552 },
        { name: 'Chapel',            x: 575, y: 510, width: 310, height: 100, color: '#F1948A', centerX: 730, centerY: 560 }
    ];
}

// ────────────────────────────────────────────────
// Initialize on page load
// ────────────────────────────────────────────────
// Initialize on page load
// ────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', async function() {
    console.log('=== FLOOR SCRIPT INITIALIZING ===');
    
    // Initialize canvas AFTER DOM is ready
    canvas = document.getElementById('floorPlan');
    if (!canvas) {
        console.error('CRITICAL: Canvas element not found!');
        return;
    }
    
    ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('CRITICAL: Could not get 2D context!');
        return;
    }
    
    console.log('Canvas initialized:', canvas.width, 'x', canvas.height);
    console.log('Current role:', currentRole);
    console.log('Can edit:', canEdit);
    
    // Add canvas click handler — route drawing (admin) OR room info popup (all roles)
    canvas.addEventListener('click', function(e) {
        const rect   = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = ((e.clientX - rect.left) * scaleX - panX) / zoomLevel;
        const y = ((e.clientY - rect.top)  * scaleY - panY) / zoomLevel;

        // Admin route-drawing mode: add waypoint
        if (currentRole === 'admin' && routeDrawMode) {
            waypoints.push({ x, y });
            updateWaypointList();
            drawFloorPlan();
            return;
        }

        // All roles: check if a room was clicked and show info popup
        const clicked = rooms.find(r => x >= r.x && x <= r.x + r.width && y >= r.y && y <= r.y + r.height);
        if (clicked) {
            showRoomPopup(clicked, e.clientX, e.clientY);
        } else {
            closeRoomPopup();
        }
    });

    // Close popup when clicking outside the canvas
    document.addEventListener('click', function(e) {
        if (!canvas.contains(e.target)) closeRoomPopup();
    });
    
    await initializeFloorPlan();
    console.log('=== FLOOR SCRIPT INITIALIZATION COMPLETE ===');

    // ── Mobile: scale canvas to fit its container ──────────────────
    function resizeCanvasToContainer() {
        var container = canvas.parentElement; // .canvas-wrapper
        if (!container) return;
        var maxW = container.clientWidth;
        if (maxW <= 0) return;
        var nativeW = 900, nativeH = 700;
        if (maxW < nativeW) {
            var ratio = maxW / nativeW;
            canvas.style.width  = maxW + 'px';
            canvas.style.height = Math.round(nativeH * ratio) + 'px';
        } else {
            canvas.style.width  = '';
            canvas.style.height = '';
        }
    }
    resizeCanvasToContainer();
    window.addEventListener('resize', function() {
        resizeCanvasToContainer();
        drawFloorPlan();
    });
});

async function initializeFloorPlan() {
    await loadRoomsFromDB();
    populateRoomSelects();
    await loadRoutesFromAPI();
    drawFloorPlan();
    
    // Show appropriate panel based on role
    const mainContent = document.getElementById('mainContent');
    if (currentRole === "admin") {
        if (mainContent) mainContent.classList.add('admin-mode');
    } else {
        if (mainContent) mainContent.classList.add('student-mode');
    }
}

// ────────────────────────────────────────────────
// Zoom controls
// ────────────────────────────────────────────────
function zoomAroundCenter(newZoom) {
    // Anchor zoom to the canvas center so the map stays centred when zooming
    const cx = canvas.width  / 2;
    const cy = canvas.height / 2;
    // World-space point currently under the canvas center
    const worldX = (cx - panX) / zoomLevel;
    const worldY = (cy - panY) / zoomLevel;
    zoomLevel = newZoom;
    // Recalculate pan so that same world point stays under canvas center
    panX = cx - worldX * zoomLevel;
    panY = cy - worldY * zoomLevel;
    drawFloorPlan();
}

function zoomIn() {
    zoomAroundCenter(Math.min(zoomLevel + 0.2, 3));
}

function zoomOut() {
    zoomAroundCenter(Math.max(zoomLevel - 0.2, 0.5));
}

function resetZoom() {
    zoomLevel = 1;
    panX = 0;
    panY = 0;
    drawFloorPlan();
}

// ────────────────────────────────────────────────
// Load / Save routes (localStorage)
// ────────────────────────────────────────────────
// loadSavedRoutes and saveRoutesToStorage are now handled by loadRoutesFromAPI() and saveRouteToAPI()
// These functions are kept as stubs for backward compatibility
function loadSavedRoutes() {
    // Deprecated - using API now
    loadRoutesFromAPI();
}

function saveRoutesToStorage() {
    // Deprecated - using API now  
    // Routes are automatically saved via API calls
}


// ────────────────────────────────────────────────
// Populate room dropdowns
// ────────────────────────────────────────────────
function populateRoomSelects() {
    const startSelect = document.getElementById('startRoom');
    const endSelect   = document.getElementById('endRoom');

    // Only populate if elements exist (admin page only)
    if (!startSelect || !endSelect) {
        console.log('Room dropdowns not found - skipping (non-admin page)');
        return;
    }

    startSelect.innerHTML = '<option value="">Select Starting Point</option>';
    endSelect.innerHTML   = '<option value="">Select Destination</option>';

    rooms.forEach(room => {
        const opt1 = document.createElement('option');
        opt1.value = room.name;
        opt1.text  = room.name;
        startSelect.appendChild(opt1);

        const opt2 = document.createElement('option');
        opt2.value = room.name;
        opt2.text  = room.name;
        endSelect.appendChild(opt2);
    });

    const isAdmin = currentRole === 'admin';
    startSelect.disabled = !isAdmin;
    endSelect.disabled   = !isAdmin;
}

// ────────────────────────────────────────────────
// Draw the entire floor plan + current route
// ────────────────────────────────────────────────
// ── Pin helpers ──────────────────────────────────
function drawPin(cx, cy) {
    const R  = 11;   // ball radius
    const pH = 34;   // total pin height
    // ease-out drop: starts 50px above
    const t  = pinAnim < 1 ? 1 - Math.pow(1 - pinAnim, 3) : 1;
    const oy = (1 - t) * -50;  // offset y (negative = above)

    ctx.save();

    // shadow ellipse on room surface
    if (pinAnim >= 0.9) {
        const a = 0.22 * t;
        const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, 16);
        g.addColorStop(0, `rgba(0,0,0,${a})`);
        g.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.ellipse(cx, cy, 16, 6, 0, 0, Math.PI*2);
        ctx.fill();
    }

    // ripple ring
    if (pinAnim >= 1 && pinRipple > 0) {
        ctx.beginPath();
        ctx.arc(cx, cy, 26 * pinRipple, 0, Math.PI*2);
        ctx.strokeStyle = `rgba(239,68,68,${0.55*(1-pinRipple)})`;
        ctx.lineWidth = 2.5;
        ctx.stroke();
    }

    const bx = cx;
    const by = cy - pH + oy;   // ball centre (top of pin)

    // stem
    ctx.beginPath();
    ctx.moveTo(bx, by + pH - R*0.4);   // tip
    ctx.lineTo(bx - R*0.5, by + R*0.7);
    ctx.lineTo(bx + R*0.5, by + R*0.7);
    ctx.closePath();
    ctx.fillStyle = '#b91c1c';
    ctx.shadowColor = 'rgba(0,0,0,0.25)';
    ctx.shadowBlur = 5;
    ctx.fill();

    // ball
    ctx.beginPath();
    ctx.arc(bx, by, R, 0, Math.PI*2);
    const g2 = ctx.createRadialGradient(bx-3, by-3, 1, bx, by, R);
    g2.addColorStop(0, '#ff6b6b');
    g2.addColorStop(1, '#dc2626');
    ctx.fillStyle = g2;
    ctx.shadowBlur = 8;
    ctx.fill();

    // shine
    ctx.beginPath();
    ctx.arc(bx-3, by-3, R*0.36, 0, Math.PI*2);
    ctx.fillStyle = 'rgba(255,255,255,0.5)';
    ctx.shadowBlur = 0;
    ctx.fill();

    ctx.restore();
}

function pinRoom(room) {
    pinnedRoom = room;
    pinAnim    = 0;
    pinRipple  = 0;
    if (pinRaf) cancelAnimationFrame(pinRaf);

    function drop() {
        pinAnim = Math.min(pinAnim + 0.1, 1);
        drawFloorPlan();
        if (pinAnim < 1) { pinRaf = requestAnimationFrame(drop); }
        else { ripple(); }
    }
    function ripple() {
        pinRipple += 0.05;
        drawFloorPlan();
        if (pinRipple < 1) { pinRaf = requestAnimationFrame(ripple); }
        else { pinRipple = 0; drawFloorPlan(); }
    }
    pinRaf = requestAnimationFrame(drop);
}

function unpinRoom() {
    pinnedRoom = null;
    pinAnim = 0; pinRipple = 0;
    if (pinRaf) { cancelAnimationFrame(pinRaf); pinRaf = null; }
    document.querySelectorAll('.class-chip').forEach(el => el.classList.remove('active'));
}

function drawFloorPlan() {
    ctx.save();
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    ctx.translate(panX, panY);
    ctx.scale(zoomLevel, zoomLevel);

    // Draw rooms
    rooms.forEach(room => {
        ctx.fillStyle = room.color;
        ctx.fillRect(room.x, room.y, room.width, room.height);
        ctx.strokeStyle = '#333';
        ctx.lineWidth = 2;
        ctx.strokeRect(room.x, room.y, room.width, room.height);

        ctx.fillStyle = '#000';
        ctx.font = 'bold 14px Lexend, Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        const words = room.name.split(' ');
        if (words.length > 1 && room.width < 150) {
            ctx.fillText(words[0], room.centerX, room.centerY - 8);
            ctx.fillText(words.slice(1).join(' '), room.centerX, room.centerY + 8);
        } else {
            ctx.fillText(room.name, room.centerX, room.centerY);
        }
    });

    // Draw current route being edited / viewed
    if (waypoints.length > 0 || (startRoom && endRoom)) {
        ctx.strokeStyle = '#FF6B6B';
        ctx.lineWidth = 6;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.shadowColor = 'rgba(255, 107, 107, 0.4)';
        ctx.shadowBlur = 10;
        ctx.beginPath();

        if (startRoom) {
            if (waypoints.length > 0) {
                ctx.moveTo(startRoom.centerX, startRoom.centerY);
                ctx.lineTo(waypoints[0].x, waypoints[0].y);
            } else if (endRoom) {
                ctx.moveTo(startRoom.centerX, startRoom.centerY);
                ctx.lineTo(endRoom.centerX, endRoom.centerY);
            }
        }

        for (let i = 0; i < waypoints.length - 1; i++) {
            ctx.moveTo(waypoints[i].x, waypoints[i].y);
            ctx.lineTo(waypoints[i + 1].x, waypoints[i + 1].y);
        }

        if (endRoom && waypoints.length > 0) {
            ctx.moveTo(waypoints[waypoints.length - 1].x, waypoints[waypoints.length - 1].y);
            ctx.lineTo(endRoom.centerX, endRoom.centerY);
        }

        ctx.stroke();
        ctx.shadowBlur = 0;

        // Waypoint circles + numbers
        waypoints.forEach((wp, index) => {
            ctx.fillStyle = '#4ECDC4';
            ctx.beginPath();
            ctx.arc(wp.x, wp.y, 12, 0, 2 * Math.PI);
            ctx.fill();
            ctx.strokeStyle = '#2C3E50';
            ctx.lineWidth = 3;
            ctx.stroke();

            ctx.fillStyle = '#FFF';
            ctx.font = 'bold 14px JetBrains Mono, monospace';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(index + 1, wp.x, wp.y);
        });
    }

    // Start / End markers
    if (startRoom) {
        ctx.fillStyle = '#10b981';
        ctx.beginPath();
        ctx.arc(startRoom.centerX, startRoom.centerY, 14, 0, 2 * Math.PI);
        ctx.fill();
        ctx.strokeStyle = '#065f46';
        ctx.lineWidth = 3;
        ctx.stroke();
        ctx.fillStyle = '#FFF';
        ctx.font = 'bold 16px Arial';
        ctx.fillText('S', startRoom.centerX, startRoom.centerY);
    }

    if (endRoom) {
        ctx.fillStyle = '#ef4444';
        ctx.beginPath();
        ctx.arc(endRoom.centerX, endRoom.centerY, 14, 0, 2 * Math.PI);
        ctx.fill();
        ctx.strokeStyle = '#991b1b';
        ctx.lineWidth = 3;
        ctx.stroke();
        ctx.fillStyle = '#FFF';
        ctx.font = 'bold 16px Arial';
        ctx.fillText('E', endRoom.centerX, endRoom.centerY);
    }

    // Draw pin on top (screen-space so it stays crisp at any zoom)
    if (pinnedRoom) {
        // Glowing border around room
        ctx.save();
        ctx.translate(panX, panY);
        ctx.scale(zoomLevel, zoomLevel);
        ctx.strokeStyle = '#ef4444';
        ctx.lineWidth = 3 / zoomLevel;
        ctx.shadowColor = 'rgba(239,68,68,0.55)';
        ctx.shadowBlur = 12 / zoomLevel;
        ctx.strokeRect(pinnedRoom.x - 2/zoomLevel, pinnedRoom.y - 2/zoomLevel,
                       pinnedRoom.width + 4/zoomLevel, pinnedRoom.height + 4/zoomLevel);
        ctx.restore();

        // Pin in screen coordinates
        const sx = pinnedRoom.centerX * zoomLevel + panX;
        const sy = pinnedRoom.centerY * zoomLevel + panY;
        drawPin(sx, sy);
    }

    ctx.restore();
}

// ────────────────────────────────────────────────
// Admin-only route creation functions
// ────────────────────────────────────────────────

function startDrawingRoute() {
    if (currentRole !== 'admin') return;

    const startName = document.getElementById('startRoom').value;
    const endName   = document.getElementById('endRoom').value;

    if (!startName || !endName) {
        alert('⚠️ Please select both starting point and destination first!');
        return;
    }
    if (startName === endName) {
        alert('⚠️ Starting point and destination cannot be the same!');
        return;
    }

    startRoom = rooms.find(r => r.name === startName);
    endRoom   = rooms.find(r => r.name === endName);

    routeDrawMode = true;
    document.getElementById('drawRouteBtn').classList.add('active');
    document.getElementById('modeIndicator').classList.add('active');
    canvas.style.cursor = 'crosshair';

    drawFloorPlan();
    showSuccessMessage('✏️ Route drawing mode activated! Click on the map to add waypoints.');
}

function findDirectRoute() {
    if (currentRole !== 'admin') return;

    const startName = document.getElementById('startRoom').value;
    const endName   = document.getElementById('endRoom').value;

    if (!startName || !endName) {
        alert('⚠️ Please select both starting point and destination!');
        return;
    }

    startRoom = rooms.find(r => r.name === startName);
    endRoom   = rooms.find(r => r.name === endName);
    waypoints = [];
    routeDrawMode = false;

    document.getElementById('drawRouteBtn').classList.remove('active');
    document.getElementById('modeIndicator').classList.remove('active');

    updateWaypointList();
    updateRouteInfo();
    drawFloorPlan();
    showSuccessMessage('✅ Direct route created successfully!');
}

function completeRoute() {
    if (currentRole !== 'admin') return;

    if (!startRoom || !endRoom) {
        alert('⚠️ Please select start and end points!');
        return;
    }

    routeDrawMode = false;
    document.getElementById('drawRouteBtn').classList.remove('active');
    document.getElementById('modeIndicator').classList.remove('active');
    updateRouteInfo();
    showSuccessMessage('✅ Route completed! You can now save it.');
}

// ────────────────────────────────────────────────
// Save / Load / Delete / Export / Import
// ────────────────────────────────────────────────

async function saveRoute() {
    if (currentRole !== 'admin') return;

    console.log('=== SAVE ROUTE DEBUG ===');
    console.log('startRoom:', startRoom);
    console.log('endRoom:', endRoom);
    console.log('waypoints:', waypoints);

    if (!startRoom || !endRoom) {
        alert('⚠️ Please select start and end rooms from the dropdowns first!');
        return;
    }

    const routeName = document.getElementById('routeName').value.trim();
    if (!routeName) {
        alert('⚠️ Please enter a name for the route!');
        return;
    }

    const routeDescription = document.getElementById('routeDescription').value.trim();
    const visibleToStudents = document.getElementById('visibleToStudents').checked;

    const routeData = {
        name: routeName,
        description: routeDescription,
        startRoom: startRoom.name,
        endRoom: endRoom.name,
        waypoints: waypoints,
        visibleToStudents: visibleToStudents
    };

    console.log('Route data being sent:', routeData);

    const result = await saveRouteToAPI(routeData);
    
    if (result.success) {
        await loadRoutesFromAPI(); // Reload routes from server
        
        document.getElementById('routeName').value = '';
        document.getElementById('routeDescription').value = '';
        document.getElementById('visibleToStudents').checked = true;

        showSuccessMessage(`💾 Route "${routeName}" saved successfully!`);
    } else {
        alert(`❌ Failed to save route: ${result.message || 'Unknown error'}`);
    }
}

function loadRoute(id) {
    const route = savedRoutes.find(r => r.id === id);
    if (!route) {
        console.error('Route not found:', id);
        return;
    }

    // Only update dropdowns if they exist (admin page only)
    const startSelect = document.getElementById('startRoom');
    const endSelect = document.getElementById('endRoom');
    if (startSelect && endSelect) {
        startSelect.value = route.startRoom;
        endSelect.value = route.endRoom;
    }

    // Set the start and end rooms
    startRoom = rooms.find(r => r.name === route.startRoom);
    endRoom   = rooms.find(r => r.name === route.endRoom);
    waypoints = Array.isArray(route.waypoints) ? [...route.waypoints] : [];

    routeDrawMode = false;
    if (currentRole === 'admin') {
        const drawBtn = document.getElementById('drawRouteBtn');
        const modeIndicator = document.getElementById('modeIndicator');
        if (drawBtn) drawBtn.classList.remove('active');
        if (modeIndicator) modeIndicator.classList.remove('active');
    }

    // Update UI (admin only)
    if (typeof updateWaypointList === 'function') {
        updateWaypointList();
    }
    if (typeof updateRouteInfo === 'function') {
        updateRouteInfo();
    }
    
    // Draw the route on the canvas
    drawFloorPlan();

    showSuccessMessage(`✅ Route "${route.name}" loaded successfully!`);
    console.log('✅ Route displayed:', route.name);
}

async function deleteRoute(id) {
    if (currentRole !== 'admin') return;
    if (!confirm('🗑️ Are you sure you want to delete this route? This action cannot be undone.')) return;

    const result = await deleteRouteFromAPI(id);
    
    if (result.success) {
        await loadRoutesFromAPI(); // Reload routes from server
        showSuccessMessage('🗑️ Route deleted successfully!');
    } else {
        alert(`❌ Failed to delete route: ${result.message || 'Unknown error'}`);
    }
}

async function toggleVisibility(id) {
    if (currentRole !== 'admin') return;

    const route = savedRoutes.find(r => r.id === id);
    if (!route) return;

    // Toggle the visibility
    const newVisibility = !route.visibleToStudents;
    
    // Update via API
    const routeData = {
        id: route.id,
        name: route.name,
        description: route.description,
        startRoom: route.startRoom,
        endRoom: route.endRoom,
        waypoints: route.waypoints,
        visibleToStudents: newVisibility
    };

    const result = await saveRouteToAPI(routeData);
    
    if (result.success) {
        await loadRoutesFromAPI(); // Reload routes from server
        const status = newVisibility ? 'visible to' : 'hidden from';
        showSuccessMessage(`${newVisibility ? '👁️' : '🔒'} Route is now ${status} students!`);
    } else {
        alert(`❌ Failed to update route: ${result.message || 'Unknown error'}`);
    }
}

function exportRoutes() {
    if (currentRole !== 'admin') return;
    if (savedRoutes.length === 0) {
        alert('⚠️ No routes to export!');
        return;
    }

    const dataStr = JSON.stringify(savedRoutes, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `floor-plan-routes-${Date.now()}.json`;
    link.click();
    URL.revokeObjectURL(url);

    showSuccessMessage('📤 Routes exported successfully!');
}

function importRoutes(event) {
    if (currentRole !== 'admin') return;

    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const imported = JSON.parse(e.target.result);
            if (Array.isArray(imported)) {
                const newRoutes = imported.map(route => ({
                    ...route,
                    id: Date.now() + Math.random(),
                    visibleToStudents: route.visibleToStudents !== undefined ? route.visibleToStudents : true
                }));
                savedRoutes = [...savedRoutes, ...newRoutes];
                saveRoutesToStorage();
                displaySavedRoutes();
                updateAdminStats();
                showSuccessMessage(`📥 ${newRoutes.length} route(s) imported successfully!`);
            } else {
                alert('⚠️ Invalid file format!');
            }
        } catch (error) {
            alert('⚠️ Error reading file: ' + error.message);
        }
    };
    reader.readAsText(file);
    event.target.value = '';
}

// ────────────────────────────────────────────────
// Helper functions (waypoint list, distance, messages, etc.)
// ────────────────────────────────────────────────

function calculateTotalDistance() {
    let total = 0;
    if (waypoints.length === 0) {
        if (startRoom && endRoom) {
            total = Math.sqrt(
                Math.pow(endRoom.centerX - startRoom.centerX, 2) +
                Math.pow(endRoom.centerY - startRoom.centerY, 2)
            );
        }
    } else {
        let prevX = startRoom ? startRoom.centerX : waypoints[0].x;
        let prevY = startRoom ? startRoom.centerY : waypoints[0].y;

        waypoints.forEach(wp => {
            total += Math.sqrt(Math.pow(wp.x - prevX, 2) + Math.pow(wp.y - prevY, 2));
            prevX = wp.x;
            prevY = wp.y;
        });

        if (endRoom) {
            total += Math.sqrt(
                Math.pow(endRoom.centerX - prevX, 2) +
                Math.pow(endRoom.centerY - prevY, 2)
            );
        }
    }
    return Math.round(total / 10);
}

function updateWaypointList() {
    const list = document.getElementById('waypointList');
    if (!list) return;

    if (waypoints.length === 0 && !(startRoom && endRoom)) {
        list.innerHTML = '<p style="text-align:center;color:var(--gray-500);font-size:.85rem;">No waypoints yet</p>';
        return;
    }

    const steps = generateDirections(startRoom, waypoints, endRoom);
    if (!steps.length) {
        list.innerHTML = '<p style="text-align:center;color:var(--gray-500);font-size:.85rem;">Add waypoints to generate directions</p>';
        return;
    }

    list.innerHTML = steps.map((step, i) => `
        <div class="waypoint-item" style="flex-direction:column;align-items:flex-start;gap:2px;padding:7px 10px;">
            <div style="display:flex;justify-content:space-between;width:100%;align-items:center;">
                <span style="font-weight:600;font-size:.85rem;">${step.icon} Step ${i + 1}</span>
                ${(currentRole === 'admin' && step.waypointIndex !== undefined)
                    ? `<span class="delete-waypoint" onclick="deleteWaypoint(${step.waypointIndex})" title="Remove waypoint">✕</span>`
                    : ''}
            </div>
            <span style="font-size:.82rem;color:#374151;">${step.instruction}</span>
            ${step.distance ? `<span style="font-size:.76rem;color:#9ca3af;">~${step.distance}m</span>` : ''}
        </div>
    `).join('');
}

function deleteWaypoint(index) {
    if (currentRole !== 'admin') return;
    waypoints.splice(index, 1);
    updateWaypointList();
    drawFloorPlan();
    showSuccessMessage('🗑️ Waypoint removed!');
}

function undoLastWaypoint() {
    if (currentRole !== 'admin') return;
    if (waypoints.length > 0) {
        waypoints.pop();
        updateWaypointList();
        drawFloorPlan();
        showSuccessMessage('↩️ Last waypoint removed!');
    }
}

function updateRouteInfo() {
    if (!startRoom || !endRoom) return;

    const routeInfo = document.getElementById('routeInfo');
    const routeDetails = document.getElementById('routeDetails');
    
    // Elements don't exist on non-admin pages
    if (!routeInfo || !routeDetails) {
        return;
    }
    
    const distance = calculateTotalDistance();

    routeInfo.style.display = 'block';
    routeDetails.innerHTML = `
        <p style="margin-top: 10px;"><strong>From:</strong> ${startRoom.name}</p>
        <p><strong>To:</strong> ${endRoom.name}</p>
        <p><strong>Waypoints:</strong> ${waypoints.length}</p>
        <p><strong>Distance:</strong> ~${distance} meters</p>
        <p style="margin-top: 8px; color: var(--gray-600);">
            ${waypoints.length > 0 ? '🎨 Custom route' : '➡️ Direct route'}
        </p>
    `;
}

function clearRoute() {
    waypoints = [];
    startRoom = null;
    endRoom = null;
    routeDrawMode = false;

    if (currentRole === 'admin') {
        document.getElementById('drawRouteBtn').classList.remove('active');
        document.getElementById('modeIndicator').classList.remove('active');
    }
    const routeInfoEl = document.getElementById('routeInfo');
    if (routeInfoEl) routeInfoEl.style.display = 'none';

    updateWaypointList();
    drawFloorPlan();
    showSuccessMessage('🗑️ Route cleared!');
}

function resetAll() {
    clearRoute();
    if (currentRole === 'admin') {
        document.getElementById('startRoom').value = '';
        document.getElementById('endRoom').value = '';
        document.getElementById('routeName').value = '';
        document.getElementById('routeDescription').value = '';
        document.getElementById('visibleToStudents').checked = true;
    }
}

function showSuccessMessage(message) {
    const msgEl = document.getElementById('successMessage');
    if (!msgEl) return;
    msgEl.textContent = message;
    msgEl.classList.add('show');
    setTimeout(() => msgEl.classList.remove('show'), 4000);
}

// ────────────────────────────────────────────────
// Display saved routes (admin view)
// ────────────────────────────────────────────────
function displaySavedRoutes() {
    const container = document.getElementById('savedRoutesList');
    if (!container) return;

    if (savedRoutes.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <p><strong>No saved routes yet</strong></p>
                <p style="font-size: 0.9em; margin-top: 5px;">Create and save your first route!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    savedRoutes.forEach(route => {
        const card = document.createElement('div');
        card.className = 'saved-route-card';

        const date = new Date(route.createdAt).toLocaleDateString();
        const visibilityIcon = route.visibleToStudents ? '👁️' : '🔒';
        const visibilityText = route.visibleToStudents ? 'Visible to students' : 'Hidden from students';

        const aStart = rooms.find(r => r.name === route.startRoom);
        const aEnd   = rooms.find(r => r.name === route.endRoom);
        const aSteps = generateDirections(aStart, route.waypoints || [], aEnd);
        const aStepsHtml = aSteps.length
            ? `<details style="margin:6px 0;">
                   <summary style="cursor:pointer;font-size:.8rem;font-weight:700;color:#6b7280;user-select:none;">📋 Directions (${aSteps.length} steps)</summary>
                   <div style="margin-top:6px;">${aSteps.map((s,i) => `
                       <div style="display:flex;gap:7px;align-items:flex-start;margin-bottom:4px;">
                           <span style="flex-shrink:0;width:20px;height:20px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#374151;">${i+1}</span>
                           <div><span style="font-size:.8rem;color:#111827;">${s.icon} ${s.instruction}</span>
                           ${s.distance ? `<span style="font-size:.73rem;color:#9ca3af;display:block;">~${s.distance}m</span>` : ''}</div>
                       </div>`).join('')}</div>
               </details>`
            : '';
        card.innerHTML = `
            <h4>${route.name} ${visibilityIcon}</h4>
            ${route.description ? `<p>${route.description}</p>` : ''}
            <p><strong>From:</strong> ${route.startRoom}</p>
            <p><strong>To:</strong> ${route.endRoom}</p>
            ${aStepsHtml}
            <p style="font-size: 0.85em; color: ${route.visibleToStudents ? 'var(--success)' : 'var(--gray-500)'};">
                <strong>${visibilityText}</strong></p>
            <div class="route-meta">
                <span>📏 ~${route.distance}m</span>
                <span>📅 ${date}</span>
            </div>
            <div class="route-actions">
                <button class="btn btn-success btn-small" onclick="loadRoute(${route.id})">📂 Load</button>
                <button class="btn btn-secondary btn-small" onclick="toggleVisibility(${route.id})">${route.visibleToStudents ? '🔒 Hide' : '👁️ Show'}</button>
                <button class="btn btn-clear btn-small" onclick="deleteRoute(${route.id})">🗑️ Delete</button>
            </div>
        `;

        container.appendChild(card);
    });
}

// ────────────────────────────────────────────────
// Student / Teacher / Registrar view of public routes
// ────────────────────────────────────────────────
// Helper: find route search/list elements regardless of role-specific IDs
function getRouteSearchEl() {
    return document.getElementById('studentRouteSearch')
        || document.getElementById('registrarRouteSearch')
        || document.getElementById('teacherRouteSearch')
        || document.getElementById('hrRouteSearch');
}
function getRoutesListEl() {
    return document.getElementById('studentRoutesList')
        || document.getElementById('registrarRoutesList')
        || document.getElementById('teacherRoutesList')
        || document.getElementById('hrRoutesList');
}

function displayStudentRoutes() {
    console.log('=== DISPLAY STUDENT ROUTES ===');
    console.log('Total saved routes:', savedRoutes.length);
    console.log('Saved routes:', savedRoutes);
    
    const searchEl = getRouteSearchEl();
    if (searchEl) searchEl.value = '';
    filterStudentRoutes();
}

function filterStudentRoutes() {
    const searchInput = getRouteSearchEl();
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const container = getRoutesListEl();
    if (!container) return;
    const publicRoutes = savedRoutes.filter(r => r.visibleToStudents);
    
    console.log('Public routes (visibleToStudents=true):', publicRoutes.length);
    console.log('Public routes data:', publicRoutes);

    if (publicRoutes.length === 0) {
        console.log('No public routes found - showing empty state');
        container.innerHTML = `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <p><strong>No routes available</strong></p>
                <p style="font-size: 0.9em; margin-top: 5px;">Check back later for available routes</p>
            </div>
        `;
        return;
    }

    const filtered = publicRoutes.filter(route => {
        return (
            route.name.toLowerCase().includes(searchTerm) ||
            (route.description && route.description.toLowerCase().includes(searchTerm)) ||
            route.startRoom.toLowerCase().includes(searchTerm) ||
            route.endRoom.toLowerCase().includes(searchTerm)
        );
    });

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                </svg>
                <p><strong>No routes found</strong></p>
                <p style="font-size: 0.9em; margin-top: 5px;">Try a different search term</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    filtered.forEach(route => {
        const card = document.createElement('div');
        card.className = 'saved-route-card';

        const date = new Date(route.createdAt).toLocaleDateString();

        const highlight = (text, term) => {
            if (!term || !text) return text;
            const regex = new RegExp(`(${term})`, 'gi');
            return text.replace(regex, '<mark style="background:#fef3c7; padding:2px 4px; border-radius:3px;">$1</mark>');
        };

        const rStart = rooms.find(r => r.name === route.startRoom);
        const rEnd   = rooms.find(r => r.name === route.endRoom);
        const steps  = generateDirections(rStart, route.waypoints || [], rEnd);
        const stepsHtml = steps.length
            ? `<div style="margin:8px 0 4px;border-top:1px solid #f0f0f0;padding-top:8px;">
                   <div style="font-size:.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Turn-by-Turn Directions</div>
                   ${steps.map((s, i) => `
                       <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:5px;">
                           <span style="flex-shrink:0;width:22px;height:22px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#374151;">${i+1}</span>
                           <div>
                               <span style="font-size:.82rem;color:#111827;">${s.icon} ${s.instruction}</span>
                               ${s.distance ? `<span style="font-size:.75rem;color:#9ca3af;display:block;">~${s.distance}m</span>` : ''}
                           </div>
                       </div>`).join('')}
               </div>`
            : '';

        card.innerHTML = `
            <h4>${highlight(route.name, searchTerm)}</h4>
            ${route.description ? `<p>${highlight(route.description, searchTerm)}</p>` : ''}
            <p><strong>From:</strong> ${highlight(route.startRoom, searchTerm)}</p>
            <p><strong>To:</strong> ${highlight(route.endRoom, searchTerm)}</p>
            ${stepsHtml}
            <div class="route-meta" style="margin-top:6px;">
                <span>📏 ~${route.distance}m</span>
                <span>📅 ${date}</span>
            </div>
            <div class="route-actions">
                <button class="btn btn-success btn-small" onclick="loadRoute(${route.id})" style="width:100%">🗺️ View Route</button>
            </div>
        `;

        container.appendChild(card);
    });
}

// ────────────────────────────────────────────────
// Admin stats
// ────────────────────────────────────────────────
function updateAdminStats() {
    if (currentRole !== 'admin') return;

    const statsEl = document.getElementById('adminStats');
    if (savedRoutes.length > 0) {
        statsEl.style.display = 'grid';
        document.getElementById('totalRoutesCount').textContent = savedRoutes.length;
        document.getElementById('publicRoutesCount').textContent = savedRoutes.filter(r => r.visibleToStudents).length;
    } else {
        statsEl.style.display = 'none';
    }
}

// Aliases so role-specific oninput handlers work correctly
function filterRegistrarRoutes() { filterStudentRoutes(); }
function filterTeacherRoutes() { filterStudentRoutes(); }
function filterHrRoutes() { filterStudentRoutes(); }


// ────────────────────────────────────────────────
// Turn-by-Turn Direction Engine
// ────────────────────────────────────────────────

/**
 * Given a start room, array of waypoints, and end room,
 * returns an array of human-readable direction steps.
 * Each step: { icon, instruction, distance, waypointIndex? }
 */
function generateDirections(start, wps, end) {
    if (!start || !end) return [];

    // Build the full list of points in order
    const points = [
        { x: start.centerX, y: start.centerY, label: start.name },
        ...wps.map((wp, i) => ({ x: wp.x, y: wp.y, label: `Waypoint ${i + 1}`, waypointIndex: i })),
        { x: end.centerX,   y: end.centerY,   label: end.name   }
    ];

    if (points.length < 2) return [];

    const steps = [];

    // Step 1: always "Start at <room>"
    steps.push({
        icon: '🟢',
        instruction: `Start at <strong>${escHtml(start.name)}</strong>`,
        distance: null
    });

    for (let i = 1; i < points.length; i++) {
        const from = points[i - 1];
        const to   = points[i];
        const dx   = to.x - from.x;
        const dy   = to.y - from.y;
        const dist = Math.round(Math.sqrt(dx * dx + dy * dy) / 10); // pixels → ~meters

        // Angle of this segment (degrees, 0 = right, 90 = down, etc.)
        const angle = Math.atan2(dy, dx) * 180 / Math.PI;

        let icon, direction;

        if (i === 1) {
            // First move: use absolute compass direction
            direction = absoluteDirection(angle);
            icon      = directionIcon(angle);
            const dest = i === points.length - 1
                ? `<strong>${escHtml(end.name)}</strong>`
                : `Waypoint ${i}`;
            steps.push({
                icon,
                instruction: `Head ${direction} toward ${dest}`,
                distance: dist,
                waypointIndex: to.waypointIndex
            });
        } else {
            // Subsequent moves: compute turn relative to previous segment
            const prevFrom = points[i - 2];
            const prevDx   = from.x - prevFrom.x;
            const prevDy   = from.y - prevFrom.y;
            const prevAngle = Math.atan2(prevDy, prevDx) * 180 / Math.PI;

            let turn = angle - prevAngle;
            // Normalise to -180..180
            while (turn >  180) turn -= 360;
            while (turn < -180) turn += 360;

            const dest = i === points.length - 1
                ? `<strong>${escHtml(end.name)}</strong>`
                : `Waypoint ${i}`;

            if (Math.abs(turn) < 20) {
                icon        = '⬆️';
                direction   = `Continue straight toward ${dest}`;
            } else if (turn > 20 && turn <= 60) {
                icon        = '↗️';
                direction   = `Bear right toward ${dest}`;
            } else if (turn > 60 && turn <= 120) {
                icon        = '➡️';
                direction   = `Turn right toward ${dest}`;
            } else if (turn > 120) {
                icon        = '↩️';
                direction   = `Sharp right toward ${dest}`;
            } else if (turn < -20 && turn >= -60) {
                icon        = '↖️';
                direction   = `Bear left toward ${dest}`;
            } else if (turn < -60 && turn >= -120) {
                icon        = '⬅️';
                direction   = `Turn left toward ${dest}`;
            } else {
                icon        = '↪️';
                direction   = `Sharp left toward ${dest}`;
            }

            steps.push({
                icon,
                instruction: direction,
                distance: dist,
                waypointIndex: to.waypointIndex
            });
        }
    }

    // Final step: arrive
    steps.push({
        icon: '🏁',
        instruction: `Arrive at <strong>${escHtml(end.name)}</strong>`,
        distance: null
    });

    return steps;
}

function absoluteDirection(angleDeg) {
    // Normalize angle
    let a = ((angleDeg % 360) + 360) % 360;
    if (a >= 337.5 || a < 22.5)  return 'East';
    if (a < 67.5)  return 'Southeast';
    if (a < 112.5) return 'South';
    if (a < 157.5) return 'Southwest';
    if (a < 202.5) return 'West';
    if (a < 247.5) return 'Northwest';
    if (a < 292.5) return 'North';
    return 'Northeast';
}

function directionIcon(angleDeg) {
    let a = ((angleDeg % 360) + 360) % 360;
    if (a >= 337.5 || a < 22.5)  return '➡️';
    if (a < 67.5)  return '↘️';
    if (a < 112.5) return '⬇️';
    if (a < 157.5) return '↙️';
    if (a < 202.5) return '⬅️';
    if (a < 247.5) return '↖️';
    if (a < 292.5) return '⬆️';
    return '↗️';
}

// ────────────────────────────────────────────────
// Room Info Popup
// ────────────────────────────────────────────────

function showRoomPopup(room, clientX, clientY) {
    let popup = document.getElementById('roomInfoPopup');
    if (!popup) {
        popup = document.createElement('div');
        popup.id = 'roomInfoPopup';
        popup.style.cssText = `
            position: fixed;
            z-index: 9999;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10);
            padding: 0;
            min-width: 260px;
            max-width: 320px;
            pointer-events: auto;
            font-family: Lexend, Arial, sans-serif;
            animation: popupFadeIn .15s ease;
            overflow: hidden;
        `;
        // Inject keyframe animation once
        if (!document.getElementById('roomPopupStyle')) {
            const style = document.createElement('style');
            style.id = 'roomPopupStyle';
            style.textContent = `
                @keyframes popupFadeIn {
                    from { opacity:0; transform:scale(.95) translateY(-4px); }
                    to   { opacity:1; transform:scale(1)  translateY(0); }
                }
                #roomInfoPopup .popup-header {
                    padding: 12px 16px 10px;
                    display: flex; align-items: center; gap: 10px;
                }
                #roomInfoPopup .popup-icon {
                    width: 36px; height: 36px; border-radius: 8px;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 18px; flex-shrink: 0;
                }
                #roomInfoPopup .popup-title {
                    font-size: 1rem; font-weight: 700; color: #1a1a2e; line-height: 1.2;
                }
                #roomInfoPopup .popup-subtitle {
                    font-size: 0.75rem; color: #6b7280; margin-top: 1px;
                }
                #roomInfoPopup .popup-body {
                    padding: 0 16px 14px;
                }
                #roomInfoPopup .popup-row {
                    display: flex; justify-content: space-between; align-items: center;
                    padding: 5px 0; border-bottom: 1px solid #f3f4f6; font-size: .83rem;
                }
                #roomInfoPopup .popup-row:last-child { border-bottom: none; }
                #roomInfoPopup .popup-label { color: #9ca3af; font-weight: 500; }
                #roomInfoPopup .popup-value { color: #111827; font-weight: 600; text-align: right; }
                #roomInfoPopup .popup-close {
                    position: absolute; top: 8px; right: 10px;
                    background: none; border: none; cursor: pointer;
                    font-size: 16px; color: #9ca3af; line-height: 1;
                    padding: 2px 4px; border-radius: 4px;
                }
                #roomInfoPopup .popup-close:hover { background: #f3f4f6; color: #374151; }
                #roomInfoPopup .popup-badge {
                    display: inline-block; padding: 2px 10px; border-radius: 20px;
                    font-size: .75rem; font-weight: 600;
                }
            `;
            document.head.appendChild(style);
        }
        document.body.appendChild(popup);
    }

    // Determine icon and colors by room type
    const typeConfig = {
        'Classroom':      { icon: '🎓', bg: '#dbeafe', color: '#1d4ed8' },
        'Administrative': { icon: '🏛️', bg: '#fef9c3', color: '#854d0e' },
        'Service':        { icon: '🛠️', bg: '#dcfce7', color: '#166534' },
        'Common Area':    { icon: '🌿', bg: '#fce7f3', color: '#9d174d' },
        'Laboratory':     { icon: '🔬', bg: '#ede9fe', color: '#5b21b6' },
        'Other':          { icon: '📦', bg: '#f3f4f6', color: '#374151' },
    };
    const cfg = typeConfig[room.type] || typeConfig['Other'];

    const floorLabel  = room.floor    ? `Floor ${room.floor}` : '—';
    const capLabel    = room.capacity ? `${room.capacity} pax` : '—';
    const buildLabel  = room.building || '—';
    const typeLabel   = room.type     || '—';
    const purposeText = room.purpose  || null;
    const imageUrl    = room.image_url || null;

    const imageSection = imageUrl
        ? `<div style="padding:0 0 10px;">
               <img src="${imageUrl}" alt="${escHtml(room.name)}"
                    style="width:100%;height:160px;object-fit:cover;display:block;border-bottom:1px solid #f0f0f0;">
           </div>`
        : '';

    const purposeSection = purposeText
        ? `<div style="padding:8px 16px 10px;border-bottom:1px solid #f3f4f6;">
               <div style="font-size:.78rem;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Purpose</div>
               <div style="font-size:.875rem;color:#374151;line-height:1.55;">${escHtml(purposeText)}</div>
           </div>`
        : '';

    popup.innerHTML = `
        <button class="popup-close" onclick="closeRoomPopup()" title="Close">✕</button>
        ${imageSection}
        <div class="popup-header" style="background: linear-gradient(135deg, ${room.color}44, ${room.color}22); border-bottom: 3px solid ${room.color}; ${imageUrl ? 'border-top:none;' : ''}">
            <div class="popup-icon" style="background:${cfg.bg}; color:${cfg.color};">${cfg.icon}</div>
            <div>
                <div class="popup-title">${escHtml(room.name)}</div>
                <div class="popup-subtitle">${escHtml(buildLabel)}</div>
            </div>
        </div>
        ${purposeSection}
        <div class="popup-body">
            <div class="popup-row">
                <span class="popup-label">Type</span>
                <span class="popup-value">
                    <span class="popup-badge" style="background:${cfg.bg};color:${cfg.color};">${escHtml(typeLabel)}</span>
                </span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Floor</span>
                <span class="popup-value">${escHtml(floorLabel)}</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Capacity</span>
                <span class="popup-value">${escHtml(capLabel)}</span>
            </div>
        </div>
    `;

    // Position popup: keep it within viewport
    popup.style.display = 'block';
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const pw = popup.offsetWidth  || 260;
    const ph = popup.offsetHeight || 200;
    let left = clientX + 14;
    let top  = clientY - 20;
    if (left + pw > vw - 10) left = clientX - pw - 14;
    if (top  + ph > vh - 10) top  = vh - ph - 10;
    if (top < 10) top = 10;
    popup.style.left = left + 'px';
    popup.style.top  = top  + 'px';
}

function closeRoomPopup() {
    const popup = document.getElementById('roomInfoPopup');
    if (popup) popup.style.display = 'none';
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Hover cursor: pointer when over a room ──
function addRoomHoverCursor() {
    if (!canvas) return;
    canvas.addEventListener('mousemove', function(e) {
        if (currentRole === 'admin' && routeDrawMode) {
            canvas.style.cursor = 'crosshair';
            return;
        }
        const rect   = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = ((e.clientX - rect.left) * scaleX - panX) / zoomLevel;
        const y = ((e.clientY - rect.top)  * scaleY - panY) / zoomLevel;
        const over = rooms.some(r => x >= r.x && x <= r.x + r.width && y >= r.y && y <= r.y + r.height);
        canvas.style.cursor = over ? 'pointer' : 'default';
    });
}

// Call after canvas is initialized
window.addEventListener('DOMContentLoaded', function() {
    // Use a slight delay to ensure canvas is ready
    setTimeout(addRoomHoverCursor, 300);
});
