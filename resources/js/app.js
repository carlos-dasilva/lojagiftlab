import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine; Alpine.start();
const getFavorites=()=>{try{return JSON.parse(localStorage.getItem('giftlab_favorites')||'[]').map(Number).filter(Number.isInteger)}catch{return[]}};
const renderFavorites=()=>{const f=getFavorites();document.querySelectorAll('[data-favorites-count]').forEach(e=>e.textContent=f.length);document.querySelectorAll('[data-favorite]').forEach(b=>{const active=f.includes(Number(b.dataset.favorite));b.classList.toggle('active',active);b.setAttribute('aria-pressed',String(active));});document.querySelectorAll('[data-favorites-link]').forEach(link=>{const url=new URL(link.href,location.origin);url.searchParams.set('favorites',f.join(','));link.href=url.toString();});};
document.addEventListener('click',e=>{const b=e.target.closest('[data-favorite]');if(!b)return;const id=Number(b.dataset.favorite),f=getFavorites();localStorage.setItem('giftlab_favorites',JSON.stringify(f.includes(id)?f.filter(x=>x!==id):[...f,id]));renderFavorites();});
const copyText=async text=>{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(text);return;}const area=document.createElement('textarea');area.value=text;area.style.position='fixed';area.style.opacity='0';document.body.appendChild(area);area.select();document.execCommand('copy');area.remove();};
document.addEventListener('click',async event=>{
    const shareButton=event.target.closest('[data-share]');
    const copyButton=event.target.closest('[data-copy-link]');
    if(!shareButton&&!copyButton)return;
    const content=(shareButton||copyButton).closest('[data-share-content]');
    const title=content?.dataset.shareTitle||document.title;
    const text=content?.dataset.shareText||document.querySelector('meta[name="description"]')?.content||'';
    const image=content?.dataset.shareImage||'';
    if(copyButton){await copyText(location.href);window.GiftLabModal?.({type:'success',title:'Link copiado',message:'O link do produto foi copiado para a área de transferência.'});return;}
    openSharePicker({title,text,image,url:location.href});
});
renderFavorites();

const mobileHeader=document.querySelector('[data-mobile-header]');
const menuToggle=mobileHeader?.querySelector('[data-menu-toggle]');
const mobileNavigation=mobileHeader?.querySelector('[data-mobile-nav]');
const setMobileMenu=open=>{if(!menuToggle||!mobileNavigation)return;menuToggle.setAttribute('aria-expanded',String(open));menuToggle.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');menuToggle.classList.toggle('is-open',open);mobileNavigation.hidden=!open;};
menuToggle?.addEventListener('click',event=>{event.stopPropagation();setMobileMenu(menuToggle.getAttribute('aria-expanded')!=='true');});
mobileNavigation?.addEventListener('click',event=>{if(event.target.closest('a'))setMobileMenu(false);});
document.addEventListener('click',event=>{if(mobileHeader&&!mobileHeader.contains(event.target))setMobileMenu(false);});
window.addEventListener('resize',()=>{if(window.innerWidth>760)setMobileMenu(false);});

const adminSidebar=document.querySelector('[data-admin-sidebar]');
const adminMenuToggle=adminSidebar?.querySelector('[data-admin-menu-toggle]');
const adminMobileMenu=adminSidebar?.querySelector('[data-admin-mobile-menu]');
const setAdminMenu=open=>{if(!adminMenuToggle||!adminMobileMenu)return;adminMenuToggle.setAttribute('aria-expanded',String(open));adminMenuToggle.setAttribute('aria-label',open?'Fechar menu administrativo':'Abrir menu administrativo');adminMenuToggle.classList.toggle('is-open',open);adminMobileMenu.classList.toggle('is-open',open);};
adminMenuToggle?.addEventListener('click',()=>setAdminMenu(adminMenuToggle.getAttribute('aria-expanded')!=='true'));
adminMobileMenu?.addEventListener('click',event=>{if(event.target.closest('a'))setAdminMenu(false);});
window.addEventListener('resize',()=>{if(window.innerWidth>760)setAdminMenu(false);});

const modalLayer=document.createElement('div');
modalLayer.className='gl-modal-layer';
modalLayer.innerHTML='<section class="gl-modal" role="dialog" aria-modal="true" aria-labelledby="gl-modal-title"><div class="gl-modal-accent"></div><div class="gl-modal-body"><div class="gl-modal-icon">i</div><h2 id="gl-modal-title"></h2><p class="gl-modal-message"></p><ul class="gl-modal-list"></ul></div><div class="gl-modal-actions"><button type="button" class="gl-cancel">Voltar</button><button type="button" class="gl-confirm">Entendi</button></div></section>';
document.body.appendChild(modalLayer);
let modalResolver=null;
const closeModal=result=>{modalLayer.classList.remove('is-open');document.body.style.overflow='';modalResolver?.(result);modalResolver=null;};
window.GiftLabModal=(options={})=>new Promise(resolve=>{modalResolver=resolve;const type=options.type||'info',modal=modalLayer.querySelector('.gl-modal');modal.dataset.type=type;modal.querySelector('.gl-modal-icon').textContent=type==='error'?'!':type==='success'?'✓':type==='warning'?'?':'i';modal.querySelector('h2').textContent=options.title||'Informação';modal.querySelector('.gl-modal-message').textContent=options.message||'';const list=modal.querySelector('.gl-modal-list');list.innerHTML='';(options.items||[]).forEach(item=>{const li=document.createElement('li');li.textContent=item;list.appendChild(li);});const cancel=modal.querySelector('.gl-cancel'),confirm=modal.querySelector('.gl-confirm');cancel.hidden=!options.confirm;confirm.textContent=options.confirmLabel||(options.confirm?'Confirmar':'Entendi');modalLayer.classList.add('is-open');document.body.style.overflow='hidden';confirm.focus();});
modalLayer.querySelector('.gl-confirm').addEventListener('click',()=>closeModal(true));
modalLayer.querySelector('.gl-cancel').addEventListener('click',()=>closeModal(false));
modalLayer.addEventListener('click',e=>{if(e.target===modalLayer)closeModal(false);});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modalLayer.classList.contains('is-open'))closeModal(false);});
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',async e=>{if(form.dataset.confirmed)return;e.preventDefault();const accepted=await window.GiftLabModal({type:'warning',title:form.dataset.confirmTitle||'Confirmar ação',message:form.dataset.confirmMessage||'Deseja continuar?',confirm:true,confirmLabel:form.dataset.confirmLabel||'Confirmar'});if(accepted){form.dataset.confirmed='1';form.submit();}}));
const feedback=document.querySelector('[data-modal-feedback]');
if(feedback){let items=[];try{items=JSON.parse(feedback.dataset.items||'[]')}catch{}window.GiftLabModal({type:feedback.dataset.type,title:feedback.dataset.title,message:feedback.dataset.message,items});feedback.remove();}
else {
    const legacyFeedback=document.querySelector('.toast,.admin-alert,.form-errors');
    if(legacyFeedback){window.GiftLabModal({type:legacyFeedback.classList.contains('form-errors')?'error':'success',title:legacyFeedback.classList.contains('form-errors')?'Revise as informações':'Tudo certo!',message:legacyFeedback.textContent.trim()});legacyFeedback.remove();}
}

const sharePicker=document.createElement('div');
sharePicker.className='share-picker-layer';
sharePicker.innerHTML=`<section class="share-picker" role="dialog" aria-modal="true" aria-labelledby="share-picker-title"><header class="share-picker-header"><h2 id="share-picker-title">Compartilhar produto</h2><button type="button" class="share-picker-close" aria-label="Fechar">×</button></header><div class="share-picker-preview"><img alt="Capa do produto"><div><strong></strong><p></p></div></div><div class="share-picker-grid"><button type="button" data-share-channel="copy"><span>🔗</span> Copiar link</button><button type="button" data-share-channel="whatsapp"><span>◉</span> WhatsApp</button><button type="button" data-share-channel="facebook"><span>f</span> Facebook</button><button type="button" data-share-channel="instagram"><span>◎</span> Instagram</button><button type="button" data-share-channel="x"><span>𝕏</span> X (Twitter)</button><button type="button" data-share-channel="email"><span>✉</span> E-mail</button><button type="button" class="share-wide" data-share-channel="native"><span>↗</span> Mais aplicativos</button></div></section>`;
document.body.appendChild(sharePicker);
let currentShare=null;
function openSharePicker(data){currentShare=data;const preview=sharePicker.querySelector('.share-picker-preview');preview.hidden=!data.image;preview.querySelector('img').src=data.image||'';preview.querySelector('strong').textContent=data.title;preview.querySelector('p').textContent=data.text;sharePicker.classList.add('is-open');document.body.style.overflow='hidden';sharePicker.querySelector('.share-picker-close').focus();}
const closeSharePicker=()=>{sharePicker.classList.remove('is-open');document.body.style.overflow='';};
const nativeShare=async data=>{
    const shareData={title:data.title,text:data.text,url:data.url};
    if(data.image&&navigator.canShare){try{const response=await fetch(data.image);const blob=await response.blob();const extension=blob.type.split('/')[1]||'jpg';const file=new File([blob],`gift-lab-produto.${extension}`,{type:blob.type});if(navigator.canShare({...shareData,files:[file]}))shareData.files=[file];}catch{}}
    if(!navigator.share)return false;
    await navigator.share(shareData);
    return true;
};
sharePicker.querySelector('.share-picker-close').addEventListener('click',closeSharePicker);
sharePicker.addEventListener('click',async event=>{
    if(event.target===sharePicker){closeSharePicker();return;}
    const button=event.target.closest('[data-share-channel]');
    if(!button||!currentShare)return;
    const data=currentShare,channel=button.dataset.shareChannel,fullText=`${data.title}\n\n${data.text}\n\n${data.url}`;
    closeSharePicker();
    if(channel==='copy'){await copyText(data.url);window.GiftLabModal({type:'success',title:'Link copiado',message:'O link do produto foi copiado.'});return;}
    if(channel==='whatsapp'){window.open(`https://wa.me/?text=${encodeURIComponent(fullText)}`,'_blank','noopener');return;}
    if(channel==='facebook'){window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(data.url)}`,'_blank','noopener');return;}
    if(channel==='x'){window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(`${data.title} — ${data.text}`)}&url=${encodeURIComponent(data.url)}`,'_blank','noopener');return;}
    if(channel==='email'){location.href=`mailto:?subject=${encodeURIComponent(data.title)}&body=${encodeURIComponent(fullText)}`;return;}
    try{
        const shared=await nativeShare(data);
        if(shared)return;
        await copyText(fullText);
        if(channel==='instagram')window.open('https://www.instagram.com/','_blank','noopener');
        window.GiftLabModal({type:'info',title:'Conteúdo copiado',message:'Este navegador não permite enviar diretamente para esse aplicativo. Cole o conteúdo que foi copiado.'});
    }catch(error){if(error.name!=='AbortError')window.GiftLabModal({type:'error',title:'Não foi possível compartilhar',message:'Tente outra opção de compartilhamento.'});}
});
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&sharePicker.classList.contains('is-open'))closeSharePicker();});
let invalidModalScheduled=false;
document.addEventListener('invalid',event=>{event.preventDefault();if(invalidModalScheduled)return;invalidModalScheduled=true;setTimeout(()=>{const invalidFields=[...document.querySelectorAll(':invalid')];const items=invalidFields.map(field=>{const label=field.closest('label')?.querySelector('span')?.textContent||field.name;return `${label.trim()}: preencha este campo corretamente.`;});window.GiftLabModal({type:'error',title:'Algumas informações precisam de atenção',message:'Revise os campos indicados antes de continuar.',items}).then(()=>invalidFields[0]?.focus());invalidModalScheduled=false;},0);},true);

const salesLinksEditor=document.querySelector('[data-sales-links-editor]');
if(salesLinksEditor){
    const list=salesLinksEditor.querySelector('[data-sales-links-list]');
    const empty=salesLinksEditor.querySelector('[data-sales-links-empty]');
    const instagramDirectUrl='https://www.instagram.com/lojagiftlab/';
    const normalizeChannel=value=>value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim().toLowerCase();
    const fillInstagramUrl=row=>{const channel=row.querySelector('input[name$="[channel]"]');const url=row.querySelector('input[name$="[url]"]');if(normalizeChannel(channel?.value||'')==='direct do instagram'){url.value=instagramDirectUrl;url.readOnly=true;}else if(url?.readOnly){url.readOnly=false;url.value='';}};
    const refreshEmpty=()=>{empty.hidden=Boolean(list.querySelector('[data-sales-link-row]'));};
    const addRow=()=>{
        const index=`new_${Date.now()}`;
        const row=document.createElement('div');
        row.className='sales-link-row';
        row.dataset.salesLinkRow='';
        row.innerHTML=`<label><span>Local de venda</span><input name="sales_links[${index}][channel]" list="sales-channel-suggestions" placeholder="Ex.: Mercado Livre"></label><label><span>Link direto do anúncio</span><input type="url" name="sales_links[${index}][url]" placeholder="https://..."></label><label><span>Valor neste local (R$)</span><input type="number" step="0.01" min="0.01" name="sales_links[${index}][price]"></label><button type="button" class="remove-sales-link" data-remove-sales-link aria-label="Remover este local">Remover</button>`;
        list.appendChild(row);
        refreshEmpty();
        row.querySelector('input').focus();
    };
    salesLinksEditor.querySelector('[data-add-sales-link]').addEventListener('click',addRow);
    salesLinksEditor.addEventListener('input',event=>{if(event.target.matches('input[name$="[channel]"]'))fillInstagramUrl(event.target.closest('[data-sales-link-row]'));});
    salesLinksEditor.querySelectorAll('[data-sales-link-row]').forEach(fillInstagramUrl);
    salesLinksEditor.addEventListener('click',event=>{const button=event.target.closest('[data-remove-sales-link]');if(!button)return;button.closest('[data-sales-link-row]').remove();refreshEmpty();});
}

const videoEditor=document.querySelector('[data-video-editor]');
if(videoEditor){const list=videoEditor.querySelector('[data-video-list]');videoEditor.querySelector('[data-add-video]').addEventListener('click',()=>{const index=`new_${Date.now()}`;const row=document.createElement('div');row.className='dynamic-row';row.dataset.videoRow='';row.innerHTML=`<label><span>Link do YouTube</span><input type="url" name="videos[${index}][url]" placeholder="https://youtu.be/..."></label><label><span>Título opcional</span><input name="videos[${index}][title]"></label><button type="button" data-remove-video>Remover</button>`;list.appendChild(row);row.querySelector('input').focus()});videoEditor.addEventListener('click',event=>event.target.closest('[data-remove-video]')?.closest('[data-video-row]')?.remove())}

document.querySelectorAll('[data-media-thumb]').forEach(button=>button.addEventListener('click',()=>{const stage=document.querySelector('[data-media-stage]');if(!stage)return;document.querySelectorAll('[data-media-thumb]').forEach(item=>item.classList.remove('active'));button.classList.add('active');stage.innerHTML=button.dataset.mediaType==='video'?`<iframe src="${button.dataset.mediaSrc}" title="${button.dataset.mediaTitle}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`:`<img src="${button.dataset.mediaSrc}" alt="${button.dataset.mediaTitle}">`}));

const shippingForm=document.querySelector('[data-shipping-form]');
shippingForm?.addEventListener('submit',async event=>{event.preventDefault();const result=shippingForm.querySelector('[data-shipping-result]');const button=shippingForm.querySelector('button');button.disabled=true;result.textContent='Consultando opções de entrega...';try{const response=await fetch(shippingForm.action,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({postal_code:new FormData(shippingForm).get('postal_code')})});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{})[0]?.[0]||'Não foi possível calcular o frete.');result.innerHTML=data.quotes?.length?data.quotes.map(q=>`<article><strong>${q.company?`${q.company} · `:''}${q.name}</strong><span>R$ ${Number(q.price).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span><small>${q.days} dias úteis no total</small></article>`).join(''):'Nenhuma opção de entrega dos Correios foi encontrada para este CEP.'}catch(error){result.textContent=error.message}finally{button.disabled=false}});

const imagesManager=document.querySelector('[data-images-manager]');
imagesManager?.addEventListener('click',async event=>{
    const button=event.target.closest('[data-image-action]');
    if(!button)return;
    const deleting=button.dataset.imageAction==='delete';
    if(deleting){const accepted=await window.GiftLabModal({type:'warning',title:'Excluir esta imagem?',message:'A imagem será removida permanentemente do produto.',confirm:true,confirmLabel:'Excluir imagem'});if(!accepted)return;}
    button.disabled=true;
    try{
        const response=await fetch(button.dataset.url,{method:deleting?'DELETE':'PATCH',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}});
        if(!response.ok)throw new Error('request_failed');
        const result=await response.json();
        await window.GiftLabModal({type:'success',title:'Tudo certo!',message:result.message});
        location.reload();
    }catch{
        button.disabled=false;
        window.GiftLabModal({type:'error',title:'Não foi possível alterar a imagem',message:'Atualize a página e tente novamente.'});
    }
});

const creditEditor=document.querySelector('[data-credit-editor]');
if(creditEditor){
    const list=creditEditor.querySelector('[data-credit-item-list]');
    const template=creditEditor.querySelector('[data-credit-item-template]');
    const toggleCustomName=row=>{const select=row.querySelector('[data-credit-product]');const custom=row.querySelector('[data-credit-custom-name]');const input=custom?.querySelector('input');const isCustom=!select?.value;custom.hidden=!isCustom;if(input)input.required=isCustom;};
    creditEditor.querySelectorAll('[data-credit-item-row]').forEach(toggleCustomName);
    creditEditor.addEventListener('change',event=>{if(event.target.matches('[data-credit-product]'))toggleCustomName(event.target.closest('[data-credit-item-row]'));});
    creditEditor.querySelector('[data-add-credit-item]')?.addEventListener('click',()=>{const key=`new_${Date.now()}`;const wrapper=document.createElement('div');wrapper.innerHTML=template.innerHTML.replaceAll('__NAME__',`items[${key}]`);const row=wrapper.firstElementChild;list.appendChild(row);toggleCustomName(row);row.querySelector('select').focus();});
    creditEditor.addEventListener('click',event=>{const remove=event.target.closest('[data-remove-credit-item]');if(!remove)return;if(list.querySelectorAll('[data-credit-item-row]').length===1){window.GiftLabModal({type:'info',title:'A venda precisa de um item',message:'Adicione outro item antes de remover este.'});return;}remove.closest('[data-credit-item-row]').remove();});
}

const saleItemEditor=document.querySelector('[data-sale-item-editor]');
if(saleItemEditor){
    const product=saleItemEditor.querySelector('[data-sale-product]');
    const custom=saleItemEditor.querySelector('[data-sale-custom-name]');
    const customInput=custom.querySelector('input');
    const refresh=()=>{const generic=product.value==='other';custom.hidden=!generic;customInput.required=generic;};
    product.addEventListener('change',refresh);
    refresh();
}

const creditReceiptModal=document.querySelector('[data-credit-receipt-modal]');
if(creditReceiptModal){
    const form=creditReceiptModal.querySelector('[data-credit-receipt-form]');
    const close=()=>{creditReceiptModal.hidden=true;document.body.style.overflow='';};
    document.addEventListener('click',event=>{const button=event.target.closest('[data-receive-credit]');if(!button)return;form.action=button.dataset.url;form.querySelector('[name="received_on"]').min=button.dataset.saleDate;creditReceiptModal.querySelector('[data-credit-receipt-customer]').textContent=button.dataset.customer;creditReceiptModal.hidden=false;document.body.style.overflow='hidden';form.querySelector('[name="received_on"]').focus();});
    creditReceiptModal.querySelector('[data-close-credit-receipt]').addEventListener('click',close);
    creditReceiptModal.addEventListener('click',event=>{if(event.target===creditReceiptModal)close();});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!creditReceiptModal.hidden)close();});
}
