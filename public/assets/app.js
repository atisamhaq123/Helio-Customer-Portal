'use strict';
const menu = document.querySelector('.mobile-menu');
const sidebar = document.querySelector('.sidebar');
const scrim = document.querySelector('.sidebar-scrim');
function toggleMenu(open) { sidebar?.classList.toggle('open', open); if (scrim) scrim.hidden = !open; menu?.setAttribute('aria-expanded', String(open)); if (!open) menu?.focus(); }
menu?.addEventListener('click', () => toggleMenu(!sidebar.classList.contains('open')));
scrim?.addEventListener('click', () => toggleMenu(false));
document.addEventListener('keydown', event => { if (event.key === 'Escape' && sidebar?.classList.contains('open')) toggleMenu(false); });
document.querySelector('#document-search')?.addEventListener('input', event => {
    let visible = 0;
    const term = event.target.value.trim().toLowerCase();
    document.querySelectorAll('.document-item').forEach(item => { item.hidden = !item.dataset.title.includes(term); if (!item.hidden) visible++; });
    document.querySelector('#document-empty').hidden = visible !== 0;
});

const canvas = document.querySelector('#energy-chart');
if (canvas) {
    const context = canvas.getContext('2d');
    const tooltip = document.querySelector('#chart-tooltip');
    const scale = Number(canvas.dataset.scale) || 1;
    let period = 'day';
    let geometry;
    const periods = {
        day: { labels: ['12 AM','4 AM','8 AM','12 PM','4 PM','8 PM','12 AM'], solar: [0,0,0,0,0,0.1,0.4,0.9,1.7,2.8,3.6,4.2,4.5,4.1,3.2,2.5,1.7,0.8,0.3,0,0,0,0,0,0], usage: [0.6,0.55,0.5,0.5,0.5,0.7,0.9,1.3,1.5,1.2,1.0,1.2,1.4,1.25,1.2,1.15,1.3,1.8,2.2,2.3,1.8,1.2,0.85,0.7,0.6], max: 6, unit: 'kW', caption: 'Today · demo data' },
        week: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], solar: [26.8,31.2,28.7,33.6,32.4,35.1,30.8], usage: [20.1,18.2,21.6,19.4,18.6,23.1,21.8], max: 45, unit: 'kWh', caption: 'Sample week · demo data' },
        month: { labels: ['Day 1','Day 5','Day 10','Day 15','Day 20','Day 25','Day 30'], solar: Array.from({length:30},(_,i)=>28+Math.sin(i*.7)*6+(i%3)), usage: Array.from({length:30},(_,i)=>19+Math.cos(i*.5)*3+(i%2)), max: 45, unit: 'kWh', caption: 'Sample month · demo data' }
    };
    function draw() {
        const box = canvas.getBoundingClientRect();
        if (!box.width || !context) return;
        const ratio = window.devicePixelRatio || 1;
        canvas.width = box.width * ratio; canvas.height = box.height * ratio;
        context.setTransform(ratio,0,0,ratio,0,0);
        const width=box.width, height=box.height;
        const left=35, right=9, top=15, bottom=26;
        const plotW=width-left-right, plotH=height-top-bottom;
        const data=periods[period], max=data.max*scale;
        geometry={left,top,plotW,plotH,data,max};
        context.clearRect(0,0,width,height);
        context.font='9px "DM Sans", sans-serif';
        context.fillStyle='#6d7c91'; context.textAlign='right';
        for (let i=0;i<=3;i++) {
            const y=top+plotH*i/3;
            context.fillText((max*(3-i)/3).toFixed(max>10?0:1),left-10,y+3);
            context.beginPath(); context.strokeStyle='#25303c';context.lineWidth=.7;context.setLineDash([3,4]);context.moveTo(left,y);context.lineTo(width-right,y);context.stroke();
        }
        context.setLineDash([]);context.textAlign='center';
        data.labels.forEach((label,i)=>{const x=left+plotW*i/(data.labels.length-1);context.fillText(label,Math.max(20,Math.min(width-20,x)),height-6);});
        const coords = values => values.map((value,i)=>[left+plotW*i/(values.length-1),top+plotH-value*scale/max*plotH]);
        function line(points,color,fill) {
            function trace() {context.moveTo(...points[0]);for(let i=1;i<points.length;i++){const prev=points[i-1],cur=points[i],mid=(prev[0]+cur[0])/2;context.bezierCurveTo(mid,prev[1],mid,cur[1],cur[0],cur[1]);}}
            if(fill){const gradient=context.createLinearGradient(0,top,0,top+plotH);gradient.addColorStop(0,fill);gradient.addColorStop(1,'rgba(105,229,205,0)');context.beginPath();trace();context.lineTo(points.at(-1)[0],top+plotH);context.lineTo(left,top+plotH);context.closePath();context.fillStyle=gradient;context.fill();}
            context.beginPath();trace();context.strokeStyle=color;context.lineWidth=2;context.stroke();
        }
        line(coords(data.solar),'#69e5cd','rgba(105,229,205,.19)');line(coords(data.usage),'#7ca7ff','rgba(124,167,255,.045)');
    }
    document.querySelectorAll('[data-period]').forEach(button=>button.addEventListener('click',()=>{period=button.dataset.period;document.querySelectorAll('[data-period]').forEach(b=>b.classList.toggle('active',b===button));document.querySelector('#chart-date').textContent=periods[period].caption;tooltip.hidden=true;draw();}));
    canvas.addEventListener('pointermove',event=>{
        if(!geometry)return;
        const bounds=canvas.getBoundingClientRect(), x=event.clientX-bounds.left;
        const index=Math.max(0,Math.min(geometry.data.solar.length-1,Math.round((x-geometry.left)/geometry.plotW*(geometry.data.solar.length-1))));
        const label=period==='day'?`${String(index%24).padStart(2,'0')}:00`:period==='week'?geometry.data.labels[index]:`Day ${index+1}`;
        tooltip.replaceChildren();
        const title=document.createElement('strong');title.textContent=`${label} · Demo`;tooltip.append(title,document.createElement('br'));
        tooltip.append(`Solar: ${(geometry.data.solar[index]*scale).toFixed(1)} ${geometry.data.unit}`,document.createElement('br'),`Usage: ${(geometry.data.usage[index]*scale).toFixed(1)} ${geometry.data.unit}`);
        tooltip.hidden=false;tooltip.style.left=`${Math.max(0,Math.min(bounds.width-155,x+12))}px`;tooltip.style.top='20px';
    });
    canvas.addEventListener('pointerleave',()=>{tooltip.hidden=true;});
    new ResizeObserver(draw).observe(canvas);
    document.fonts?.ready.then(draw);
    draw();
}
