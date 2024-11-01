    const { registerCheckoutFilters } = window.wc.blocksCheckout;

    const modifyItemName = (defaultValue, extensions, args) => {

        const customStatus = extensions?.woo_custom_stock_status?.custom_status || '';

        // Return the name with a placeholder for stock status
        return `
            <span class="cart-item-name">${defaultValue}</span>
            ${customStatus}
        `;
    };

    registerCheckoutFilters('cart-item-extension', {
        itemName: modifyItemName,
    });
