# 🛒 Orders & Cart - Integração Front-end

## 📋 Visão Geral
Instruções para integração do sistema de carrinho de compras e pedidos com o front-end e-commerce.

## 🛒 Gestão de Carrinho

### 1. Obter Carrinho do Usuário
```typescript
interface CartItem {
  raffle_uuid: string;
  raffle_title: string;
  ticket_quantity: number;
  ticket_price: number;
  subtotal: number;
}

interface Cart {
  uuid: string;
  items: CartItem[];
  total_items: number;
  total_amount: number;
  expires_at: string;
}

interface CartResponse {
  status: 'success';
  message: string;
  data: {
    cart: Cart;
  };
  meta: {
    execution_time_ms: number;
  };
}

async function getCart(authToken: string): Promise<CartResponse> {
  const response = await fetch('/api/v1/customer/cart', {
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Salvar UUID do carrinho para operações futuras
    localStorage.setItem('cart_uuid', data.data.cart.uuid);
  }
  
  return data;
}
```

### 2. Adicionar Item ao Carrinho
```typescript
interface AddCartItemRequest {
  raffle_uuid: string;
  ticket_quantity: number;
}

async function addItemToCart(
  itemData: AddCartItemRequest,
  authToken: string
): Promise<CartResponse> {
  const response = await fetch('/api/v1/customer/cart/items', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(itemData)
  });
  
  const data = await response.json();
  
  // Analytics
  if (data.status === 'success') {
    gtag('event', 'add_to_cart', {
      currency: 'BRL',
      value: itemData.ticket_quantity * 2.5, // Assumindo preço médio
      items: [{
        item_id: itemData.raffle_uuid,
        item_name: 'Raffle Ticket',
        quantity: itemData.ticket_quantity
      }]
    });
  }
  
  return data;
}
```

### 3. Remover Item do Carrinho
```typescript
async function removeItemFromCart(
  raffleUuid: string,
  authToken: string
): Promise<{ status: string; message: string }> {
  const response = await fetch(`/api/v1/customer/cart/items/${raffleUuid}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    gtag('event', 'remove_from_cart', {
      currency: 'BRL',
      items: [{
        item_id: raffleUuid,
        item_name: 'Raffle Ticket'
      }]
    });
  }
  
  return data;
}
```

## 📦 Gestão de Pedidos

### 1. Criar Pedido do Carrinho
```typescript
interface CreateOrderRequest {
  payment_method: 'pix' | 'credit_card' | 'boleto';
  apply_wallet_balance?: boolean;
}

interface PaymentInfo {
  pix_code?: string;
  qr_code_url?: string;
  boleto_url?: string;
  credit_card_token?: string;
  expires_at: string;
}

interface OrderItem {
  raffle_title: string;
  tickets_quantity: number;
  unit_price: number;
  subtotal: number;
}

interface Order {
  uuid: string;
  status: 'pending_payment' | 'paid' | 'cancelled' | 'expired';
  total: number;
  payment_method: string;
  items: OrderItem[];
  payment_info?: PaymentInfo;
  created_at: string;
}

interface OrderResponse {
  status: 'success';
  message: string;
  data: {
    order: Order;
  };
  meta: {
    execution_time_ms: number;
  };
}

async function createOrderFromCart(
  orderData: CreateOrderRequest,
  authToken: string
): Promise<OrderResponse> {
  const response = await fetch('/api/v1/customer/orders', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(orderData)
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Salvar UUID do pedido
    localStorage.setItem('current_order_uuid', data.data.order.uuid);
    
    // Analytics - Begin Checkout
    gtag('event', 'begin_checkout', {
      currency: 'BRL',
      value: data.data.order.total,
      items: data.data.order.items.map(item => ({
        item_id: `ticket_${item.raffle_title}`,
        item_name: item.raffle_title,
        quantity: item.tickets_quantity,
        price: item.unit_price
      }))
    });
    
    // Limpar carrinho local após criar pedido
    localStorage.removeItem('cart_uuid');
  }
  
  return data;
}
```

### 2. Obter Detalhes do Pedido
```typescript
async function getOrderDetails(
  orderUuid: string,
  authToken: string
): Promise<OrderResponse> {
  const response = await fetch(`/api/v1/customer/orders/${orderUuid}`, {
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}
```

### 3. Listar Pedidos do Usuário
```typescript
interface OrderListResponse {
  status: 'success';
  message: string;
  data: {
    orders: {
      data: Order[];
      meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
      };
    };
  };
}

async function getUserOrders(
  authToken: string,
  page: number = 1,
  perPage: number = 10,
  status: string = 'all'
): Promise<OrderListResponse> {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
    status
  });
  
  const response = await fetch(`/api/v1/customer/orders?${params}`, {
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}
```

## 🎨 Componentes React

### Carrinho de Compras
```tsx
import React, { useState, useEffect } from 'react';

interface CartComponentProps {
  authToken: string;
  onCheckout?: (order: Order) => void;
}

export const CartComponent: React.FC<CartComponentProps> = ({
  authToken,
  onCheckout
}) => {
  const [cart, setCart] = useState<Cart | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isUpdating, setIsUpdating] = useState(false);
  
  useEffect(() => {
    loadCart();
  }, [authToken]);
  
  const loadCart = async () => {
    try {
      const response = await getCart(authToken);
      setCart(response.data.cart);
    } catch (error) {
      console.error('Erro ao carregar carrinho:', error);
    } finally {
      setIsLoading(false);
    }
  };
  
  const handleRemoveItem = async (raffleUuid: string) => {
    setIsUpdating(true);
    try {
      await removeItemFromCart(raffleUuid, authToken);
      await loadCart(); // Recarregar carrinho
    } catch (error) {
      console.error('Erro ao remover item:', error);
    } finally {
      setIsUpdating(false);
    }
  };
  
  const handleCheckout = async (paymentMethod: 'pix' | 'credit_card' | 'boleto') => {
    if (!cart || cart.items.length === 0) return;
    
    setIsUpdating(true);
    try {
      const response = await createOrderFromCart(
        { payment_method: paymentMethod },
        authToken
      );
      
      onCheckout?.(response.data.order);
    } catch (error) {
      console.error('Erro ao criar pedido:', error);
    } finally {
      setIsUpdating(false);
    }
  };
  
  if (isLoading) {
    return (
      <div className="flex justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }
  
  if (!cart || cart.items.length === 0) {
    return (
      <div className="text-center p-8">
        <h3 className="text-lg font-semibold text-gray-600">Carrinho vazio</h3>
        <p className="text-gray-500 mt-2">Adicione alguns tickets de rifas para continuar</p>
      </div>
    );
  }
  
  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h2 className="text-2xl font-bold mb-4">Seu Carrinho</h2>
      
      {/* Items */}
      <div className="space-y-4 mb-6">
        {cart.items.map((item, index) => (
          <div key={index} className="flex justify-between items-center p-4 border rounded-lg">
            <div className="flex-1">
              <h3 className="font-semibold">{item.raffle_title}</h3>
              <p className="text-gray-600">
                {item.ticket_quantity} tickets × R$ {item.ticket_price.toFixed(2)}
              </p>
            </div>
            <div className="flex items-center space-x-4">
              <span className="font-bold text-lg">
                R$ {item.subtotal.toFixed(2)}
              </span>
              <button
                onClick={() => handleRemoveItem(item.raffle_uuid)}
                disabled={isUpdating}
                className="text-red-600 hover:text-red-800 disabled:opacity-50"
              >
                🗑️
              </button>
            </div>
          </div>
        ))}
      </div>
      
      {/* Total */}
      <div className="border-t pt-4">
        <div className="flex justify-between items-center mb-4">
          <span className="text-xl font-bold">Total:</span>
          <span className="text-2xl font-bold text-green-600">
            R$ {cart.total_amount.toFixed(2)}
          </span>
        </div>
        
        {/* Payment Methods */}
        <div className="space-y-2">
          <button
            onClick={() => handleCheckout('pix')}
            disabled={isUpdating}
            className="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 disabled:opacity-50"
          >
            {isUpdating ? 'Processando...' : 'Pagar com PIX'}
          </button>
          
          <button
            onClick={() => handleCheckout('credit_card')}
            disabled={isUpdating}
            className="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50"
          >
            Cartão de Crédito
          </button>
          
          <button
            onClick={() => handleCheckout('boleto')}
            disabled={isUpdating}
            className="w-full bg-gray-600 text-white py-2 rounded-lg font-semibold hover:bg-gray-700 disabled:opacity-50"
          >
            Boleto Bancário
          </button>
        </div>
      </div>
      
      {/* Expiration */}
      <p className="text-xs text-gray-500 mt-4 text-center">
        Carrinho expira em: {new Date(cart.expires_at).toLocaleString()}
      </p>
    </div>
  );
};
```

### Histórico de Pedidos
```tsx
import React, { useState, useEffect } from 'react';

interface OrderHistoryProps {
  authToken: string;
}

export const OrderHistory: React.FC<OrderHistoryProps> = ({ authToken }) => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  
  useEffect(() => {
    loadOrders();
  }, [currentPage]);
  
  const loadOrders = async () => {
    setIsLoading(true);
    try {
      const response = await getUserOrders(authToken, currentPage);
      setOrders(response.data.orders.data);
      setTotalPages(response.data.orders.meta.last_page);
    } catch (error) {
      console.error('Erro ao carregar pedidos:', error);
    } finally {
      setIsLoading(false);
    }
  };
  
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid': return 'text-green-600 bg-green-100';
      case 'pending_payment': return 'text-yellow-600 bg-yellow-100';
      case 'cancelled': return 'text-red-600 bg-red-100';
      case 'expired': return 'text-gray-600 bg-gray-100';
      default: return 'text-blue-600 bg-blue-100';
    }
  };
  
  const getStatusText = (status: string) => {
    switch (status) {
      case 'paid': return 'Pago';
      case 'pending_payment': return 'Aguardando Pagamento';
      case 'cancelled': return 'Cancelado';
      case 'expired': return 'Expirado';
      default: return status;
    }
  };
  
  if (isLoading) {
    return (
      <div className="flex justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }
  
  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h2 className="text-2xl font-bold mb-6">Meus Pedidos</h2>
      
      {orders.length === 0 ? (
        <div className="text-center p-8">
          <p className="text-gray-500">Nenhum pedido encontrado</p>
        </div>
      ) : (
        <>
          <div className="space-y-4">
            {orders.map((order) => (
              <div key={order.uuid} className="border rounded-lg p-4">
                <div className="flex justify-between items-start mb-3">
                  <div>
                    <h3 className="font-semibold">Pedido #{order.uuid.substring(0, 8)}</h3>
                    <p className="text-gray-600 text-sm">
                      {new Date(order.created_at).toLocaleDateString('pt-BR')}
                    </p>
                  </div>
                  <div className="flex items-center space-x-3">
                    <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(order.status)}`}>
                      {getStatusText(order.status)}
                    </span>
                    <span className="text-lg font-bold">
                      R$ {order.total.toFixed(2)}
                    </span>
                  </div>
                </div>
                
                <div className="space-y-1">
                  {order.items.map((item, index) => (
                    <div key={index} className="flex justify-between text-sm">
                      <span>{item.raffle_title} ({item.tickets_quantity} tickets)</span>
                      <span>R$ {item.subtotal.toFixed(2)}</span>
                    </div>
                  ))}
                </div>
                
                <div className="mt-3 pt-3 border-t">
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600">
                      Pagamento: {order.payment_method.toUpperCase()}
                    </span>
                    {order.status === 'pending_payment' && order.payment_info?.qr_code_url && (
                      <a
                        href={order.payment_info.qr_code_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-600 hover:underline text-sm"
                      >
                        Ver QR Code PIX
                      </a>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex justify-center mt-6 space-x-2">
              <button
                onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                disabled={currentPage === 1}
                className="px-4 py-2 border rounded-lg disabled:opacity-50"
              >
                Anterior
              </button>
              
              <span className="px-4 py-2">
                Página {currentPage} de {totalPages}
              </span>
              
              <button
                onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                disabled={currentPage === totalPages}
                className="px-4 py-2 border rounded-lg disabled:opacity-50"
              >
                Próxima
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
};
```

## 💳 Integração de Pagamentos

### PIX QR Code Component
```tsx
import React, { useState, useEffect } from 'react';
import QRCode from 'qrcode.react';

interface PIXPaymentProps {
  order: Order;
  onPaymentConfirmed?: () => void;
}

export const PIXPayment: React.FC<PIXPaymentProps> = ({
  order,
  onPaymentConfirmed
}) => {
  const [timeRemaining, setTimeRemaining] = useState<string>('');
  
  useEffect(() => {
    if (!order.payment_info?.expires_at) return;
    
    const interval = setInterval(() => {
      const now = new Date().getTime();
      const expires = new Date(order.payment_info!.expires_at).getTime();
      const distance = expires - now;
      
      if (distance > 0) {
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        setTimeRemaining(`${minutes}:${seconds.toString().padStart(2, '0')}`);
      } else {
        setTimeRemaining('Expirado');
        clearInterval(interval);
      }
    }, 1000);
    
    return () => clearInterval(interval);
  }, [order.payment_info?.expires_at]);
  
  const copyPIXCode = () => {
    if (order.payment_info?.pix_code) {
      navigator.clipboard.writeText(order.payment_info.pix_code);
      alert('Código PIX copiado!');
    }
  };
  
  return (
    <div className="bg-white rounded-lg shadow-md p-6 max-w-md mx-auto">
      <h2 className="text-2xl font-bold text-center mb-4">Pagamento PIX</h2>
      
      <div className="text-center mb-6">
        <div className="text-3xl font-bold text-green-600 mb-2">
          R$ {order.total.toFixed(2)}
        </div>
        <div className="text-sm text-gray-600">
          Pedido #{order.uuid.substring(0, 8)}
        </div>
      </div>
      
      {order.payment_info?.pix_code && (
        <>
          {/* QR Code */}
          <div className="flex justify-center mb-6">
            <div className="p-4 bg-white border-2 border-gray-200 rounded-lg">
              <QRCode
                value={order.payment_info.pix_code}
                size={200}
                level="M"
                includeMargin={true}
              />
            </div>
          </div>
          
          {/* Copy Code Button */}
          <button
            onClick={copyPIXCode}
            className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 mb-4"
          >
            📋 Copiar Código PIX
          </button>
          
          {/* Instructions */}
          <div className="bg-gray-50 rounded-lg p-4 mb-4">
            <h3 className="font-semibold mb-2">Como pagar:</h3>
            <ol className="text-sm text-gray-600 space-y-1">
              <li>1. Abra o app do seu banco</li>
              <li>2. Escaneie o QR Code ou cole o código PIX</li>
              <li>3. Confirme o pagamento</li>
              <li>4. Aguarde a confirmação (até 5 minutos)</li>
            </ol>
          </div>
          
          {/* Timer */}
          {timeRemaining && (
            <div className="text-center">
              <div className="text-sm text-gray-600">Tempo restante:</div>
              <div className={`text-lg font-bold ${
                timeRemaining === 'Expirado' ? 'text-red-600' : 'text-green-600'
              }`}>
                {timeRemaining}
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
};
```

## 📊 Analytics e Conversões

### E-commerce Events
```typescript
interface EcommerceEvent {
  // Purchase Event (quando pedido é pago)
  purchase: (order: Order) => void;
  
  // Add to Cart
  addToCart: (item: { raffle_uuid: string; quantity: number; price: number }) => void;
  
  // Remove from Cart
  removeFromCart: (item: { raffle_uuid: string }) => void;
  
  // Begin Checkout
  beginCheckout: (cart: Cart) => void;
}

export const ecommerceTracking: EcommerceEvent = {
  purchase: (order) => {
    // Google Analytics 4
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'purchase', {
        transaction_id: order.uuid,
        value: order.total,
        currency: 'BRL',
        items: order.items.map(item => ({
          item_id: item.raffle_title,
          item_name: item.raffle_title,
          category: 'Raffle Tickets',
          quantity: item.tickets_quantity,
          price: item.unit_price
        }))
      });
    }
    
    // Facebook Pixel
    if (typeof window !== 'undefined' && window.fbq) {
      window.fbq('track', 'Purchase', {
        value: order.total,
        currency: 'BRL',
        content_type: 'product',
        contents: order.items.map(item => ({
          id: item.raffle_title,
          quantity: item.tickets_quantity,
          item_price: item.unit_price
        }))
      });
    }
  },
  
  addToCart: (item) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'add_to_cart', {
        currency: 'BRL',
        value: item.quantity * item.price,
        items: [{
          item_id: item.raffle_uuid,
          item_name: 'Raffle Ticket',
          quantity: item.quantity,
          price: item.price
        }]
      });
    }
  },
  
  removeFromCart: (item) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'remove_from_cart', {
        currency: 'BRL',
        items: [{
          item_id: item.raffle_uuid,
          item_name: 'Raffle Ticket'
        }]
      });
    }
  },
  
  beginCheckout: (cart) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'begin_checkout', {
        currency: 'BRL',
        value: cart.total_amount,
        items: cart.items.map(item => ({
          item_id: item.raffle_uuid,
          item_name: item.raffle_title,
          quantity: item.ticket_quantity,
          price: item.ticket_price
        }))
      });
    }
  }
};
```

## 🔄 Sincronização e Estado Global

### Context Provider (React)
```tsx
import React, { createContext, useContext, useReducer, useEffect } from 'react';

interface CartState {
  cart: Cart | null;
  orders: Order[];
  isLoading: boolean;
  error: string | null;
}

type CartAction =
  | { type: 'SET_CART'; payload: Cart }
  | { type: 'SET_ORDERS'; payload: Order[] }
  | { type: 'SET_LOADING'; payload: boolean }
  | { type: 'SET_ERROR'; payload: string | null };

const CartContext = createContext<{
  state: CartState;
  dispatch: React.Dispatch<CartAction>;
  actions: {
    loadCart: () => Promise<void>;
    addItem: (item: AddCartItemRequest) => Promise<void>;
    removeItem: (raffleUuid: string) => Promise<void>;
    createOrder: (orderData: CreateOrderRequest) => Promise<Order>;
    loadOrders: () => Promise<void>;
  };
} | null>(null);

const cartReducer = (state: CartState, action: CartAction): CartState => {
  switch (action.type) {
    case 'SET_CART':
      return { ...state, cart: action.payload };
    case 'SET_ORDERS':
      return { ...state, orders: action.payload };
    case 'SET_LOADING':
      return { ...state, isLoading: action.payload };
    case 'SET_ERROR':
      return { ...state, error: action.payload };
    default:
      return state;
  }
};

export const CartProvider: React.FC<{ children: React.ReactNode; authToken: string }> = ({
  children,
  authToken
}) => {
  const [state, dispatch] = useReducer(cartReducer, {
    cart: null,
    orders: [],
    isLoading: false,
    error: null
  });
  
  const actions = {
    loadCart: async () => {
      dispatch({ type: 'SET_LOADING', payload: true });
      try {
        const response = await getCart(authToken);
        dispatch({ type: 'SET_CART', payload: response.data.cart });
        dispatch({ type: 'SET_ERROR', payload: null });
      } catch (error: any) {
        dispatch({ type: 'SET_ERROR', payload: error.message });
      } finally {
        dispatch({ type: 'SET_LOADING', payload: false });
      }
    },
    
    addItem: async (item: AddCartItemRequest) => {
      try {
        await addItemToCart(item, authToken);
        await actions.loadCart(); // Recarregar carrinho
        ecommerceTracking.addToCart({
          raffle_uuid: item.raffle_uuid,
          quantity: item.ticket_quantity,
          price: 2.5 // Assumindo preço padrão
        });
      } catch (error: any) {
        dispatch({ type: 'SET_ERROR', payload: error.message });
        throw error;
      }
    },
    
    removeItem: async (raffleUuid: string) => {
      try {
        await removeItemFromCart(raffleUuid, authToken);
        await actions.loadCart();
        ecommerceTracking.removeFromCart({ raffle_uuid: raffleUuid });
      } catch (error: any) {
        dispatch({ type: 'SET_ERROR', payload: error.message });
        throw error;
      }
    },
    
    createOrder: async (orderData: CreateOrderRequest): Promise<Order> => {
      try {
        const response = await createOrderFromCart(orderData, authToken);
        await actions.loadCart(); // Carrinho será limpo após criar pedido
        await actions.loadOrders(); // Atualizar lista de pedidos
        
        if (state.cart) {
          ecommerceTracking.beginCheckout(state.cart);
        }
        
        return response.data.order;
      } catch (error: any) {
        dispatch({ type: 'SET_ERROR', payload: error.message });
        throw error;
      }
    },
    
    loadOrders: async () => {
      try {
        const response = await getUserOrders(authToken);
        dispatch({ type: 'SET_ORDERS', payload: response.data.orders.data });
      } catch (error: any) {
        dispatch({ type: 'SET_ERROR', payload: error.message });
      }
    }
  };
  
  // Carregar carrinho ao montar
  useEffect(() => {
    if (authToken) {
      actions.loadCart();
      actions.loadOrders();
    }
  }, [authToken]);
  
  return (
    <CartContext.Provider value={{ state, dispatch, actions }}>
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within CartProvider');
  }
  return context;
};
```

---

## 📚 Recursos Adicionais

- **Collection Postman:** `docs/postman/collections/Orders/`
- **Payment Methods:** PIX, Cartão de Crédito, Boleto
- **Status Tracking:** pending_payment, paid, cancelled, expired
- **Validation Rules:** Ver `app/Http/Requests/Customer/`
- **Service Layer:** Lógica de negócio em Services dedicados