/* global nvCtyMetaOptions */
import { CheckboxControl, TextControl } from "@wordpress/components";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import ProductSelect from "./product-select";
import { State } from "./state";

const ProductsPanel = () => {
  return (
    <PluginDocumentSettingPanel
      name="products-panel"
      className="spk-tw"
      title={__("Products", "sparks-for-woocommerce")}
    >
      <ProductSelect />
    </PluginDocumentSettingPanel>
  );
};

const RedirectUrlPanel = () => {
  const [redirectUrl, setRedirectUrl] = State("sparks_ty_redirect_url", true);

  return (
    <PluginDocumentSettingPanel
      name="redirect-url-panel"
      className="spk-tw"
      title={__("Redirect URL", "sparks-for-woocommerce")}
    >
      <TextControl
        label={__("Custom Redirect URL", "sparks-for-woocommerce")}
        value={redirectUrl || ""}
        onChange={setRedirectUrl}
        help={__(
          "If set, customers will be redirected to this URL instead of viewing this page.",
          "sparks-for-woocommerce"
        )}
        placeholder="https://example.com/thank-you"
        type="url"
      />
    </PluginDocumentSettingPanel>
  );
};

const ShippingMethodsPanel = () => {
  const [currentShippingMethod, setShippingMethod] = State(
    "nv_ty_shipping_methods"
  );

  return (
    <PluginDocumentSettingPanel
      name="shipping-methods"
      className="spk-tw"
      title={__("Shipping Methods", "sparks-for-woocommerce")}
    >
      {nvCtyMetaOptions.shipping.map((shippingMethod) => (
        <CheckboxControl
          key={shippingMethod.value}
          label={shippingMethod.label}
          checked={currentShippingMethod.includes(shippingMethod.value)}
          onChange={() => {
            setShippingMethod(shippingMethod.value);
          }}
        />
      ))}

      {nvCtyMetaOptions.shipping.length < 1 && (
        <div className="text-xs py-1 px-2 bg-wp-blue-50 border border-wp-blue-200 rounded-sm text-wp-blue-600 rounded-md">
          {__(
            "No shipping methods found. Please add some shipping methods to your store.",
            "sparks-for-woocommerce"
          )}
        </div>
      )}
    </PluginDocumentSettingPanel>
  );
};

const PaymentGatewaysPanel = () => {
  const [currentPaymentGateway, setPaymentGateway] = State(
    "nv_ty_payment_gateways"
  );

  return (
    <PluginDocumentSettingPanel
      name="payment-gateway"
      className="spk-tw"
      title={__("Payment Gateway", "sparks-for-woocommerce")}
    >
      {nvCtyMetaOptions.paymentGateway.map((paymentGateway) => (
        <CheckboxControl
          key={paymentGateway.value}
          label={paymentGateway.label}
          checked={currentPaymentGateway.includes(paymentGateway.value)}
          onChange={() => {
            setPaymentGateway(paymentGateway.value);
          }}
        />
      ))}

      {nvCtyMetaOptions.paymentGateway.length < 1 && (
        <div className="text-xs py-1 px-2 bg-wp-blue-50 border border-wp-blue-200 rounded-sm text-wp-blue-600 rounded-md">
          {__(
            "No payment gateways found. Please add some payment gateways to your store.",
            "sparks-for-woocommerce"
          )}
        </div>
      )}
    </PluginDocumentSettingPanel>
  );
};
const Component = () => {
  return (
    <>
      <ProductsPanel />
      <RedirectUrlPanel />
      <ShippingMethodsPanel />
      <PaymentGatewaysPanel />
    </>
  );
};

export default Component;
