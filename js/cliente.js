/* ================= MODAL DO CARRINHO ================= */
const cartBtn = document.querySelector('.cart');
const cartModal = document.getElementById('cart-modal');
const closeCartBtn = document.getElementById('close-cart');

cartBtn?.addEventListener('click', e => { 
  e.preventDefault(); 
  cartModal?.classList.add('active'); 
});
closeCartBtn?.addEventListener('click', () => cartModal?.classList.remove('active'));
window.addEventListener('click', e => { 
  if(e.target === cartModal) cartModal?.classList.remove('active'); 
});

/* ================= FUNÇÕES DO CARRINHO ================= */
function renderCart(data) {
  const cartItems = document.getElementById('cart-items');
  const cartTotal = document.getElementById('cart-total');
  const cartCount = document.getElementById('cart-count');

  if(cartCount) cartCount.textContent = data.cart_count ?? 0;
  if(cartTotal) cartTotal.textContent = data.cart_total ?? '0,00';
  if(!cartItems) return;

  if(!data.cart_items || Object.keys(data.cart_items).length === 0){
    cartItems.innerHTML = '<p id="empty-cart-msg">Seu carrinho está vazio.</p>';
    const footer = document.querySelector('.cart-footer');
    if(footer) footer.style.display = 'none';
    return;
  } else {
    const emptyMsg = document.getElementById('empty-cart-msg');
    if(emptyMsg) emptyMsg.remove();
  }

  const footer = document.querySelector('.cart-footer');
  if(footer) footer.style.display = 'block';

  let html = '';
  Object.entries(data.cart_items).forEach(([id, item]) => {
    const preco = parseFloat(item.preco) || 0;
    const qtd = parseInt(item.quantidade) || 0;
    const subtotal = preco * qtd;
    
    // Usar imagem específica para buquês personalizados
    const imagem = item.tipo === 'buque' ? 'bouquet.jpg' : (item.imagem || 'sem-imagem.jpg');
    
    html += `
      <li class="cart-item" data-id="${id}">
        <img src="../images/${imagem}" alt="${item.nome}">
        <div class="cart-info">
          <h4>${item.nome}</h4>
          <p>
            R$ ${preco.toFixed(2).replace('.',',')} x 
            <span class="item-quantity">${qtd}</span> = 
            R$ <span class="item-subtotal">${subtotal.toFixed(2).replace('.',',')}</span>
          </p>
          <div class="cart-actions">
            <button class="decrease">-</button>
            <button class="increase">+</button>
            <button class="remove-item">Remover</button>
          </div>
        </div>
      </li>`;
  });

  cartItems.innerHTML = html;
  bindCartItemButtons();
}

function bindCartItemButtons() {
  document.querySelectorAll('.cart-item').forEach(item => {
    const id = item.dataset.id;
    item.querySelector('.increase')?.addEventListener('click', () => updateCart(id,'increase'));
    item.querySelector('.decrease')?.addEventListener('click', () => updateCart(id,'decrease'));
    item.querySelector('.remove-item')?.addEventListener('click', () => updateCart(id,'remove'));
  });
}

function updateCart(id, action) {
  fetch('../cliente/update_cart.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`id=${encodeURIComponent(id)}&action=${encodeURIComponent(action)}`
  })
  .then(res=>res.json())
  .then(data=>{
    if(data.status==='ok') renderCart(data);
    else alert(data.msg || 'Erro ao atualizar o carrinho.');
  })
  .catch(()=>alert('Falha na comunicação com o servidor.'));
}

/* ================= ADICIONAR AO CARRINHO ================= */
document.querySelectorAll('.add-cart').forEach(btn=>{
  btn.addEventListener('click', e=>{
    e.preventDefault();
    const produto_id = btn.dataset.id;
    const modal = btn.closest('.modal');
    let cor = '';
    if(modal){
      const select = modal.querySelector('.select-cor');
      if(select) cor = select.value;
    }
    fetch('../cliente/add_to_cart.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`produto_id=${encodeURIComponent(produto_id)}&cor=${encodeURIComponent(cor)}`
    })
    .then(res=>res.json())
    .then(data=>{
      if(data.status==='ok') renderCart(data);
      else alert(data.msg || 'Erro ao adicionar ao carrinho.');
    })
    .catch(()=>alert('Falha na comunicação com o servidor.'));
  });
});

bindCartItemButtons();

/* ================= CHECKOUT ================= */
const checkoutStep = document.getElementById('checkout-step');
const cartBody = document.querySelector('.cart-body');
const cartFooter = document.querySelector('.cart-footer');
const backToCartBtn = document.getElementById('back-to-cart');
const checkoutBtn = document.querySelector('.checkout-btn');

checkoutBtn?.addEventListener('click', ()=>{
  cartBody.style.display = "none";
  cartFooter.style.display = "none";
  checkoutStep.style.display = "block";
});

backToCartBtn?.addEventListener('click', ()=>{
  checkoutStep.style.display = "none";
  cartBody.style.display = "block";
  cartFooter.style.display = "block";
});

// Concluir pedido
document.getElementById('concluir-compra')?.addEventListener('click', ()=>{
  const frete = parseFloat(document.querySelector("input[name='frete']:checked")?.value || 0);
  const pagamento = document.querySelector("input[name='pagamento']:checked")?.value || 'pix';
  const cartao_id = document.querySelector("input[name='cartao_id']:checked")?.value || '';
  const endereco_id = document.querySelector("input[name='endereco_id']:checked")?.value;

  if(!endereco_id){
    alert('Selecione um endereço para entrega.');
    return;
  }

  fetch('../cliente/checkout.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`frete=${encodeURIComponent(frete)}&pagamento=${encodeURIComponent(pagamento)}&cartao_id=${encodeURIComponent(cartao_id)}&endereco_id=${encodeURIComponent(endereco_id)}`
  })
  .then(res=>res.json())
  .then(data=>{
    if(data.status==='ok'){
      const pedidoIdEl = document.getElementById('pedidoId');
      const confirmEl = document.getElementById('pedidoConfirmado');
      if(pedidoIdEl) pedidoIdEl.textContent = data.pedido_id;
      if(confirmEl) confirmEl.style.display='flex';
      checkoutStep.style.display='none';
    } else {
      alert(data.msg || 'Erro ao processar pedido.');
    }
  })
  .catch(()=>alert('Falha na comunicação com o servidor.'));
});

// Atualiza frete no resumo
document.querySelectorAll("input[name='frete']").forEach(radio=>{
  radio.addEventListener("change", ()=>{
    const valorFrete = parseFloat(radio.value);
    const freteEl = document.getElementById("checkout-frete");
    if(freteEl) freteEl.textContent = valorFrete.toFixed(2).replace(".",",");
    atualizarTotal();
  });
});

// Mostra cartões apenas quando seleciona "Cartão"
document.querySelectorAll("input[name='pagamento']").forEach(radio=>{
  radio.addEventListener("change", ()=>{
    const cartaoOpcoes = document.getElementById("cartao-opcoes");
    if(cartaoOpcoes) cartaoOpcoes.style.display = (radio.value === "cartao") ? "block" : "none";
  });
});

function atualizarTotal(){
  const produtos = parseFloat(document.getElementById("checkout-produtos")?.dataset.total || 0);
  const frete = parseFloat(document.querySelector("input[name='frete']:checked")?.value || 0);
  const totalEl = document.getElementById("checkout-total");
  if(totalEl) totalEl.textContent = (produtos + frete).toFixed(2).replace(".",",");
}

/* ================= ADICIONAR ENDEREÇO ================= */
const btnNovoEndereco = document.getElementById('btn-novo-endereco');
const formEnderecoDiv = document.getElementById('novo-endereco-form');
const btnAddEndereco = document.getElementById('add-endereco');
const formEndereco = document.getElementById('form-endereco');
const enderecosContainer = document.getElementById('enderecos-container');

// Clicar em "Novo endereço"
btnNovoEndereco.addEventListener('click', () => {
    formEnderecoDiv.style.display = 'block';  // mostra o form
    btnNovoEndereco.style.display = 'none';   // esconde o botão
});

// Adicionar endereço
btnAddEndereco.addEventListener('click', () => {
    const formData = new FormData(formEndereco);

    fetch('add_endereco.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'sucesso') {
            alert(data.msg);

            // Adicionar novo endereço na lista de radio
            const label = document.createElement('label');
            label.innerHTML = `<input type="radio" name="endereco_id" value="${data.endereco.id_endereco}" checked>
                               <i class="fa fa-location-dot"></i> ${data.endereco.rua}, ${data.endereco.numero} - ${data.endereco.cidade}/${data.endereco.estado}`;
            enderecosContainer.appendChild(label);

            // Limpar formulário
            formEndereco.reset();

            // Ocultar formulário e mostrar o botão
            formEnderecoDiv.style.display = 'none';
            btnNovoEndereco.style.display = 'inline-block';
        } else {
            alert(data.msg);
        }
    })
    .catch(err => console.error(err));
});

/* ================= MODAIS DE PRODUTOS ================= */
document.querySelectorAll(".saiba-mais").forEach(btn=>{
  btn.addEventListener("click", ()=>{
    const modalId = btn.getAttribute("data-modal");
    const el = document.getElementById(modalId);
    if(el) el.style.display = "flex";
  });
});
document.querySelectorAll(".modal .close").forEach(btn=>{
  btn.addEventListener("click", ()=>{
    const el = btn.closest(".modal");
    if(el) el.style.display = "none";
  });
});
window.addEventListener("click", e=>{
  if(e.target.classList && e.target.classList.contains("modal")) e.target.style.display = "none";
});
/* ================= CARROSSEL + BUQUÊ ================= */
document.addEventListener("DOMContentLoaded", ()=>{
  // Carousel
  document.querySelectorAll('.carousel-container').forEach(container=>{
    const carousel = container.querySelector('.carousel');
    if(!carousel) return;

    const firstCard = carousel.querySelector('.carousel-item');
    if(!firstCard) return;
    const style = window.getComputedStyle(firstCard);
    const gap = parseInt(style.marginRight) || 20;
    const cardWidth = firstCard.offsetWidth + gap;

    const prevBtn = container.querySelector('.prev');
    const nextBtn = container.querySelector('.next');

    prevBtn?.addEventListener('click', ()=>{ carousel.scrollBy({ left: -cardWidth, behavior: 'smooth' }); });
    nextBtn?.addEventListener('click', ()=>{ carousel.scrollBy({ left: cardWidth, behavior: 'smooth' }); });

    carousel.querySelectorAll('.carousel-item').forEach(item=>{
      const incBtn = item.querySelector('.increase');
      const decBtn = item.querySelector('.decrease');
      const qtyEl = item.querySelector('.quantity');

      incBtn?.addEventListener('click', ()=>{ 
        qtyEl.textContent = parseInt(qtyEl.textContent||'0')+1; 
      });
      decBtn?.addEventListener('click', ()=>{ 
        qtyEl.textContent = Math.max(0, parseInt(qtyEl.textContent||'0')-1); 
      });
    });
  });

  // Limite embalagens
  document.querySelectorAll('.wrapper-checkbox').forEach(cb=>{
    cb.addEventListener('change', ()=>{
      const checked = document.querySelectorAll('.wrapper-checkbox:checked');
      if(checked.length > 2) cb.checked = false;
    });
  });

  // Preview buquê
  const previewBtn = document.getElementById('preview-btn');
  const previewBouquet = document.getElementById('preview-bouquet');

  if (previewBtn && previewBouquet) {
      previewBtn.addEventListener('click', ()=>{
          console.log('Clicou em ver prévia'); // Debug
          
          let total = 0;

          // Flores
          const flowersHtml = Array.from(document.querySelectorAll('#flowers-carousel .carousel-item'))
              .filter(item => {
                  const qty = parseInt(item.querySelector('.quantity')?.textContent || '0');
                  return qty > 0;
              })
              .map(item => {
                  const qty = parseInt(item.querySelector('.quantity')?.textContent || '0');
                  const name = item.querySelector('h4')?.textContent || '';
                  const preco = parseFloat(item.dataset.preco || 0);
                  total += preco * qty;

                  const cores = item.dataset.cores?.split(',') || [];
                  const options = cores.map(c => 
                      `<option value="${c.trim()}">${c.trim()}</option>`
                  ).join('');
                  
                  return `<div class="flower-item">
                              <p>${qty}x ${name}</p>
                              ${options ? `<label>Cor: <select class="flower-color" data-flower="${name}">${options}</select></label>` : ''}
                          </div>`;
              }).join('');
          
          const previewFlowers = document.getElementById('preview-flowers');
          if(previewFlowers) {
              previewFlowers.innerHTML = flowersHtml ? `<strong>Flores:</strong> ${flowersHtml}` : 'Nenhuma flor selecionada';
          }

          // Embalagens
          const wrappersHtml = Array.from(document.querySelectorAll('.wrapper-checkbox:checked'))
            .map(cb=>{
              const item = cb.closest('.carousel-item');
              const name = item?.querySelector('h4')?.textContent || '';
              const preco = parseFloat(item?.dataset.preco||0) || 0;
              total += preco;
              return `<p>${name}</p>`;
            }).join('');
          
          const previewWrappers = document.getElementById('preview-wrappers');
          if(previewWrappers) {
            previewWrappers.innerHTML = `<strong>Embalagens:</strong> ${wrappersHtml || 'Nenhuma selecionada'}`;
          }

          // Laço
          const bowRadio = document.querySelector('.bow-radio:checked');
          let bowName = '';
          if(bowRadio){
            const item = bowRadio.closest('.carousel-item');
            bowName = item?.querySelector('h4')?.textContent || '';
            total += parseFloat(item?.dataset.preco || 0) || 0;
          }
          
          const previewBow = document.getElementById('preview-bow');
          if(previewBow) {
            previewBow.innerHTML = `<strong>Laço:</strong> ${bowName || 'Nenhum selecionado'}`;
          }

          // Total
          const previewTotal = document.getElementById('preview-total');
          if(previewTotal) {
            previewTotal.textContent = total.toFixed(2).replace('.',',');
          }

          previewBouquet.style.display = 'block';
      });
  } else {
      console.log('Elementos do preview não encontrados'); // Debug
  }

  // Adicionar buquê ao carrinho
  const addBouquetBtn = document.getElementById('add-bouquet-cart');
  if (addBouquetBtn) {
      addBouquetBtn.addEventListener('click', ()=>{
          console.log('Clicou em adicionar buquê ao carrinho'); // Debug
          
          // Coletar dados das flores do preview
          const flores = Array.from(document.querySelectorAll('#preview-flowers .flower-item')).map(f=>{
              const qtyMatch = f.querySelector('p')?.textContent.match(/(\d+)x\s(.+)/);
              if(!qtyMatch) return null;
              
              const qty = parseInt(qtyMatch[1]) || 0;
              const name = qtyMatch[2]?.trim() || '';
              const color = f.querySelector('.flower-color')?.value || '';
              
              return qty > 0 ? {nome: name, quantidade: qty, cor: color} : null;
          }).filter(f => f !== null);

          // Coletar embalagens
          const embalagens = Array.from(document.querySelectorAll('.wrapper-checkbox:checked')).map(cb=>{
              return cb.closest('.carousel-item')?.querySelector('h4')?.textContent || '';
          }).filter(e => e !== '');

          // Coletar laço
          const bow = document.querySelector('.bow-radio:checked')?.closest('.carousel-item')?.querySelector('h4')?.textContent || '';
          
          // Coletar observações e preço
          const obs = document.getElementById('obs-buque')?.value || '';
          const preco = parseFloat(document.getElementById('preview-total')?.textContent.replace(',','.')) || 0;

          console.log('Dados do buquê:', {flores, embalagens, bow, obs, preco}); // Debug

          // Validar se há flores selecionadas
          if(flores.length === 0) {
              alert('Selecione pelo menos uma flor para o buquê!');
              return;
          }

          fetch('../cliente/add_to_cart.php',{
              method:'POST',
              headers:{'Content-Type':'application/x-www-form-urlencoded'},
              body:`tipo=buque&flores=${encodeURIComponent(JSON.stringify(flores))}&embalagens=${encodeURIComponent(JSON.stringify(embalagens))}&laco=${encodeURIComponent(bow)}&obs=${encodeURIComponent(obs)}&preco=${encodeURIComponent(preco)}`
          })
          .then(res => {
              console.log('Resposta do servidor:', res); // Debug
              if (!res.ok) {
                  throw new Error('Erro na resposta do servidor: ' + res.status);
              }
              return res.json();
          })
          .then(data=>{
              console.log('Dados retornados:', data); // Debug
              if(data.status === 'ok'){
                  alert('Buquê personalizado adicionado ao carrinho!');
                  renderCart(data);
                  // Fechar preview
                  const previewBouquet = document.getElementById('preview-bouquet');
                  if(previewBouquet) previewBouquet.style.display = 'none';
              } else {
                  alert(data.msg || 'Erro ao adicionar ao carrinho.');
              }
          })
          .catch(error => {
              console.error('Erro completo:', error);
              alert('Falha na comunicação com o servidor. Verifique o console para detalhes.');
          });
      });
  } else {
      console.log('Botão add-bouquet-cart não encontrado'); // Debug
  }
});
// Concluir pedido
document.getElementById('concluir-compra')?.addEventListener('click', ()=>{
  const frete = parseFloat(document.querySelector("input[name='frete']:checked")?.value || 0);
  const pagamento = document.querySelector("input[name='pagamento']:checked")?.value || 'pix';
  const cartao_id = document.querySelector("input[name='cartao_id']:checked")?.value || '';
  const endereco_id = document.querySelector("input[name='endereco_id']:checked")?.value;

  if(!endereco_id){
    alert('Selecione um endereço para entrega.');
    return;
  }

  // Mostrar loading
  const concluirBtn = document.getElementById('concluir-compra');
  const originalText = concluirBtn.innerHTML;
  concluirBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processando...';
  concluirBtn.disabled = true;

  fetch('../cliente/checkout.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`frete=${encodeURIComponent(frete)}&pagamento=${encodeURIComponent(pagamento)}&cartao_id=${encodeURIComponent(cartao_id)}&endereco_id=${encodeURIComponent(endereco_id)}`
  })
  .then(res=>res.json())
  .then(data=>{
    if(data.status==='ok'){
      mostrarConfirmacaoPedido(data);
    } else {
      alert(data.msg || 'Erro ao processar pedido.');
      concluirBtn.innerHTML = originalText;
      concluirBtn.disabled = false;
    }
  })
  .catch(()=>{
    alert('Falha na comunicação com o servidor.');
    concluirBtn.innerHTML = originalText;
    concluirBtn.disabled = false;
  });

// Função para mostrar confirmação do pedido
function mostrarConfirmacaoPedido(data) {
  const checkoutStep = document.getElementById('checkout-step');
  
  // Formatar data
  const dataPedido = new Date(data.data_pedido);
  const dataFormatada = dataPedido.toLocaleString('pt-BR');
  
  // Mapear métodos de pagamento para nomes amigáveis
  const metodosPagamento = {
    'pix': 'PIX',
    'cartao': 'Cartão de Crédito',
    'boleto': 'Boleto Bancário'
  };

  checkoutStep.innerHTML = `
    <div class="checkout-header">
      <h3>Pedido Confirmado! ✅</h3>
    </div>
    
    <div class="checkout-body">
      <div class="confirmacao-pedido">
        <div class="confirmacao-icon">
          <i class="fa fa-check-circle"></i>
        </div>
        
        <div class="confirmacao-mensagem">
          <h3>Seu pedido foi realizado com sucesso!</h3>
          <p>Agora é só aguardar a confirmação do pagamento.</p>
        </div>

        <div class="detalhes-pedido">
          <div class="detalhe-item">
            <strong>Número do Pedido:</strong>
            <span>#${data.pedido_id}</span>
          </div>
          
          <div class="detalhe-item">
            <strong>Data do Pedido:</strong>
            <span>${dataFormatada}</span>
          </div>
          
          <div class="detalhe-item">
            <strong>Total:</strong>
            <span>R$ ${parseFloat(data.total).toFixed(2).replace('.', ',')}</span>
          </div>
          
          <div class="detalhe-item">
            <strong>Método de Pagamento:</strong>
            <span>${metodosPagamento[data.pagamento] || data.pagamento}</span>
          </div>
          
          <div class="detalhe-item">
            <strong>Endereço de Entrega:</strong>
            <span>${data.endereco_entrega || 'A combinar'}</span>
          </div>
        </div>

        <div class="proximos-passos">
          <h4>Próximos Passos:</h4>
          <ul>
            <li><i class="fa fa-clock"></i> Aguarde a confirmação do pagamento</li>
            <li><i class="fa fa-truck"></i> Seu pedido será preparado para envio</li>
            <li><i class="fa fa-map-marker-alt"></i> Acompanhe o status em "Minhas Compras"</li>
          </ul>
        </div>

        <div class="acoes-confirmacao">
          <button id="ver-minhas-compras" class="btn-primario">
            <i class="fa fa-shopping-bag"></i> Ver Minhas Compras
          </button>
          <button id="voltar-inicio" class="btn-secundario">
            <i class="fa fa-home"></i> Voltar ao Início
          </button>
        </div>
      </div>
    </div>
  `;

  // Adicionar event listeners aos botões
  document.getElementById('ver-minhas-compras')?.addEventListener('click', () => {
    window.location.href = 'historico_compras.php';
  });

  document.getElementById('voltar-inicio')?.addEventListener('click', () => {
    window.location.href = 'painel_cliente.php';
  });
}

});