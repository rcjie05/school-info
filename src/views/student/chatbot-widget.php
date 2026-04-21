<!-- ── Floating Chatbot Bubble ───────────────────────────────────────────── -->
<style>
#chatBubbleBtn {
    position: fixed; bottom: 28px; right: 28px;
    width: 58px; height: 58px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-purple, #5b4e9b), var(--secondary-pink, #e91e8c));
    border: none; cursor: pointer;
    box-shadow: 0 4px 18px rgba(91,78,155,0.45);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; z-index: 9998;
    transition: transform 0.2s, box-shadow 0.2s;
}
#chatBubbleBtn:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(91,78,155,0.55); }
#chatBubbleBtn .cb-icon-open  { display: block; }
#chatBubbleBtn .cb-icon-close { display: none; }
#chatBubbleBtn.open .cb-icon-open  { display: none; }
#chatBubbleBtn.open .cb-icon-close { display: block; }
#chatBubbleBtn .cb-notif {
    position: absolute; top: 4px; right: 4px;
    width: 12px; height: 12px; background: #ef4444;
    border: 2px solid white; border-radius: 50%; display: none;
}
#chatBubbleBtn.has-notif .cb-notif { display: block; }

#chatPanel {
    position: fixed; bottom: 100px; right: 28px;
    width: 360px; max-height: 560px;
    background: white; border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.18);
    display: flex; flex-direction: column;
    z-index: 9997; overflow: hidden;
    transform: scale(0.85) translateY(20px);
    transform-origin: bottom right;
    opacity: 0; pointer-events: none;
    transition: transform 0.25s cubic-bezier(.34,1.56,.64,1), opacity 0.2s;
}
#chatPanel.open { transform: scale(1) translateY(0); opacity: 1; pointer-events: all; }

.cp-header { display:flex;align-items:center;gap:0.65rem;padding:0.9rem 1.1rem;background:linear-gradient(135deg,var(--primary-purple,#5b4e9b),var(--secondary-pink,#e91e8c));color:white;flex-shrink:0; }
.cp-avatar { width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0; }
.cp-info { flex:1; }
.cp-name { font-weight:700;font-size:0.92rem; }
.cp-status { font-size:0.73rem;opacity:0.88;display:flex;align-items:center;gap:4px; }
.cp-status-dot { width:6px;height:6px;background:#4ade80;border-radius:50%; }
.cp-actions { display:flex;gap:6px; }
.cp-btn { width:28px;height:28px;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:white;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.15s; }
.cp-btn:hover { background:rgba(255,255,255,0.35); }

.cp-chips { display:flex;flex-wrap:nowrap;gap:0.4rem;padding:0.6rem 0.9rem;overflow-x:auto;border-bottom:1px solid #f1f1f1;background:#fafafa;flex-shrink:0;scrollbar-width:none; }
.cp-chips::-webkit-scrollbar { display:none; }
.cp-chip { padding:0.28rem 0.7rem;border-radius:999px;border:1.5px solid #e5e7eb;background:white;font-size:0.73rem;font-weight:600;color:#6b7280;cursor:pointer;white-space:nowrap;transition:all 0.15s;flex-shrink:0; }
.cp-chip:hover { border-color:var(--primary-purple,#5b4e9b);color:var(--primary-purple,#5b4e9b);background:#f5f3ff; }

.cp-messages { flex:1;overflow-y:auto;padding:1rem 0.9rem;display:flex;flex-direction:column;gap:0.75rem;scrollbar-width:thin;scrollbar-color:#e5e7eb transparent; }
.cp-messages::-webkit-scrollbar { width:3px; }
.cp-messages::-webkit-scrollbar-thumb { background:#e5e7eb;border-radius:3px; }

.cm { display:flex;gap:0.5rem;align-items:flex-end;max-width:90%; }
.cm.user { align-self:flex-end;flex-direction:row-reverse; }
.cm.bot  { align-self:flex-start; }
.cm-av { width:26px;height:26px;border-radius:50%;font-size:0.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.cm.bot  .cm-av { background:linear-gradient(135deg,var(--primary-purple,#5b4e9b),var(--secondary-pink,#e91e8c)); }
.cm.user .cm-av { background:#e5e7eb; }
.cm-bubble { padding:0.6rem 0.85rem;border-radius:16px;font-size:0.82rem;line-height:1.5; }
.cm.bot  .cm-bubble { background:#f3f4f6;color:#1f2937;border-bottom-left-radius:4px; }
.cm.user .cm-bubble { background:linear-gradient(135deg,var(--primary-purple,#5b4e9b),var(--secondary-pink,#e91e8c));color:white;border-bottom-right-radius:4px; }
.cm-bubble ul { margin:0.4rem 0 0;padding-left:1.1rem; }
.cm-bubble ul li { margin-bottom:0.2rem; }
.cm-bubble a { color:inherit;text-decoration:underline;opacity:0.85; }

.cp-typing-dots { display:flex;gap:3px;align-items:center;height:16px;padding:0 2px; }
.cp-typing-dots span { width:6px;height:6px;background:#9ca3af;border-radius:50%;animation:cpBounce 1.2s infinite ease-in-out; }
.cp-typing-dots span:nth-child(2){animation-delay:0.2s}
.cp-typing-dots span:nth-child(3){animation-delay:0.4s}
@keyframes cpBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

.cp-input-bar { display:flex;align-items:center;gap:0.5rem;padding:0.7rem 0.9rem;border-top:1px solid #f1f1f1;background:white;flex-shrink:0; }
.cp-input { flex:1;padding:0.55rem 0.9rem;border:1.5px solid #e5e7eb;border-radius:999px;font-size:0.82rem;font-family:inherit;outline:none;transition:border-color 0.2s; }
.cp-input:focus { border-color:var(--primary-purple,#5b4e9b); }
.cp-send { width:34px;height:34px;border-radius:50%;border:none;background:linear-gradient(135deg,var(--primary-purple,#5b4e9b),var(--secondary-pink,#e91e8c));color:white;font-size:0.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity 0.2s; }
.cp-send:hover{opacity:0.88}.cp-send:disabled{opacity:0.35;cursor:not-allowed}

@media(max-width:480px){
    #chatPanel{width:calc(100vw - 24px);right:12px;bottom:90px;}
    #chatBubbleBtn{right:16px;bottom:20px;}
}
</style>

<button id="chatBubbleBtn" onclick="cpToggle()" title="Help & FAQ">
    <span class="cb-icon-open">🤖</span>
    <span class="cb-icon-close">✕</span>
    <span class="cb-notif"></span>
</button>

<div id="chatPanel">
    <div class="cp-header">
        <div class="cp-avatar">🤖</div>
        <div class="cp-info">
            <div class="cp-name">School Assistant</div>
            <div class="cp-status"><span class="cp-status-dot"></span> Online · Powered by your data</div>
        </div>
        <div class="cp-actions">
            <button class="cp-btn" onclick="cpClear()" title="Clear chat">🗑</button>
            <button class="cp-btn" onclick="cpToggle()" title="Close">✕</button>
        </div>
    </div>

    <div class="cp-chips">
        <button class="cp-chip" onclick="cpQuick('What is my name?')">👤 My Name</button>
        <button class="cp-chip" onclick="cpQuick('What is my student ID?')">🪪 My ID</button>
        <button class="cp-chip" onclick="cpQuick('Show my grades')">🎓 Grades</button>
        <button class="cp-chip" onclick="cpQuick('Show my schedule')">📅 Schedule</button>
        <button class="cp-chip" onclick="cpQuick('What are my subjects?')">📚 Subjects</button>
        <button class="cp-chip" onclick="cpQuick('Classes today?')">📆 Today</button>
        <button class="cp-chip" onclick="cpQuick('Where are my rooms today?')">📍 Rooms Today</button>
        <button class="cp-chip" onclick="cpQuick('Who are my teachers?')">👨‍🏫 Teachers</button>
        <button class="cp-chip" onclick="cpQuick('Latest announcements')">📢 News</button>
        <button class="cp-chip" onclick="cpQuick('How do I enroll?')">📋 Enroll</button>
        <button class="cp-chip" onclick="cpQuick('What are the school fees?')">💰 Fees</button>
        <button class="cp-chip" onclick="cpQuick('Show all available routes')">🗺️ Routes</button>
        <button class="cp-chip" onclick="cpQuick('Where is my classroom?')">📍 My Rooms</button>
        <button class="cp-chip" onclick="cpQuick('What is my section?')">🏫 My Section</button>
        <button class="cp-chip" onclick="cpQuick('Check my feedback status')">📋 My Feedback</button>
    </div>

    <div class="cp-messages" id="cpMessages">
        <div class="cm bot">
            <div class="cm-av">🤖</div>
            <div class="cm-bubble">Hi! 👋 I'm your <strong>School Assistant</strong>. I'm connected to your school data — ask me about your grades, schedule, subjects, or anything school-related!</div>
        </div>
    </div>

    <div class="cp-input-bar">
        <input type="text" class="cp-input" id="cpInput" placeholder="Ask me anything…" autocomplete="off"/>
        <button class="cp-send" id="cpSendBtn" onclick="cpSend()">➤</button>
    </div>
</div>

<script>
(function(){
    const _api = '../../api/student/chatbot.php';

    function cpScrollBottom(){ const m=document.getElementById('cpMessages'); if(m) m.scrollTop=m.scrollHeight; }

    function cpAppend(type,html){
        const m=document.getElementById('cpMessages');
        const d=document.createElement('div'); d.className='cm '+type;
        const icon=type==='bot'?'🤖':'👤';
        d.innerHTML=`<div class="cm-av">${icon}</div><div class="cm-bubble">${html}</div>`;
        m.appendChild(d); cpScrollBottom();
    }

    function cpShowTyping(){
        const m=document.getElementById('cpMessages');
        const d=document.createElement('div'); d.className='cm bot'; d.id='cpTyping';
        d.innerHTML=`<div class="cm-av">🤖</div><div class="cm-bubble"><div class="cp-typing-dots"><span></span><span></span><span></span></div></div>`;
        m.appendChild(d); cpScrollBottom();
    }

    function cpRemoveTyping(){ const el=document.getElementById('cpTyping'); if(el) el.remove(); }

    window.cpToggle=function(){
        const btn=document.getElementById('chatBubbleBtn');
        const panel=document.getElementById('chatPanel');
        const isOpen=panel.classList.toggle('open');
        btn.classList.toggle('open',isOpen);
        btn.classList.remove('has-notif');
        if(isOpen) setTimeout(()=>document.getElementById('cpInput').focus(),250);
    };

    window.cpSend=async function(){
        const input=document.getElementById('cpInput');
        const text=input.value.trim();
        if(!text) return;
        input.value='';
        document.getElementById('cpSendBtn').disabled=true;
        cpAppend('user',text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'));
        cpShowTyping();
        try {
            const res=await fetch(_api,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:text})});
            // Always try to parse as JSON; fall back gracefully on failure
            let data;
            try { data = await res.json(); } catch(_) { data = null; }
            cpRemoveTyping();
            if (data && data.reply) {
                cpAppend('bot', data.reply);
            } else if (res.redirected) {
                cpAppend('bot', 'Session expired. Please <a href="/school-mgmt-clean/public/login.html">log in again</a>.');
            } else {
                cpAppend('bot', 'Sorry, I\'m having trouble responding right now. Please try again.');
            }
        } catch(e){
            cpRemoveTyping();
            cpAppend('bot', 'Could not reach the assistant. Check your connection and try again.');
            console.error('Chatbot widget error:', e);
        }
        document.getElementById('cpSendBtn').disabled=false;
        document.getElementById('cpInput').focus();
    };

    window.cpQuick=function(text){
        const panel=document.getElementById('chatPanel');
        if(!panel.classList.contains('open')) cpToggle();
        setTimeout(()=>{ document.getElementById('cpInput').value=text; cpSend(); },150);
    };

    window.cpClear=function(){
        document.getElementById('cpMessages').innerHTML='';
        cpAppend('bot','Chat cleared! 🗑️ How can I help you?');
    };

    document.addEventListener('DOMContentLoaded',function(){
        const inp=document.getElementById('cpInput');
        if(inp) inp.addEventListener('keydown',function(e){ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();cpSend();} });
        setTimeout(()=>{ const btn=document.getElementById('chatBubbleBtn'); if(btn&&!document.getElementById('chatPanel').classList.contains('open')) btn.classList.add('has-notif'); },3000);
    });
})();
</script>
<!-- ── End Chatbot Bubble ─────────────────────────────────────────────────── -->
