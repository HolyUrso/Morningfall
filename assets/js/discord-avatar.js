function buscarAvatarDiscord(opts){
  const idEl=document.getElementById(opts.idInput);
  const avatarEl=document.getElementById(opts.avatarInput);
  const btn=document.getElementById(opts.buttonId);
  const preview=document.getElementById(opts.previewId);
  const status=document.getElementById(opts.statusId);
  const id=(idEl?.value||'').replace(/\D/g,'');
  if(id.length<15){ status.textContent='Informe um ID de Discord válido.'; return; }
  btn.disabled=true; status.textContent='Buscando avatar no Discord...';
  const fd=new FormData(); fd.append('discord_id',id); fd.append('type',opts.type||'staff');
  if(opts.entityId) fd.append('entity_id',opts.entityId);
  fetch('../api/discord_avatar.php',{method:'POST',body:fd,credentials:'same-origin'})
    .then(async r=>{
      const text=await r.text();
      let data;
      try{data=JSON.parse(text);}catch(e){throw new Error('Resposta inválida do servidor (HTTP '+r.status+').');}
      return {ok:r.ok,data};
    })
    .then(({ok,data})=>{
      if(!ok || !data.success) throw new Error(data.error||'Falha ao buscar avatar.');
      if(data.avatar){
        avatarEl.value=data.avatar;
        preview.src=data.avatar; preview.style.display='inline-block';
        status.textContent=(data.global_name||data.username||'Discord')+' • avatar encontrado.';
      }else{
        avatarEl.value=''; preview.removeAttribute('src'); preview.style.display='none';
        status.textContent=data.message||'Usuário sem avatar personalizado.';
      }
    })
    .catch(e=>status.textContent=e.message)
    .finally(()=>btn.disabled=false);
}