import apiFetch from "@wordpress/api-fetch";
import { FormTokenField, Notice, Spinner } from "@wordpress/components";
import { useDebounce } from "@wordpress/compose";
import { useSelect } from "@wordpress/data";
import {
  createInterpolateElement,
  useCallback,
  useEffect,
  useMemo,
  useState,
} from "@wordpress/element";
import { decodeEntities } from "@wordpress/html-entities";
import { __ } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";

import classNames from "classnames";
import { uniqBy } from "lodash";

const Loader = () => (
  <div style={{ padding: "16px", textAlign: "center" }}>
    <Spinner />
  </div>
);

/**
 * Hook to fetch products from WooCommerce Store API
 */
const useProducts = (search, selected = []) => {
  const [productsMap, setProductsMap] = useState(new Map());
  const [productsList, setProductsList] = useState([]);
  const [productsLoaded, setProductsLoaded] = useState(false);

  useEffect(() => {
    const query = {
      per_page: 20,
      catalog_visibility: "any",
      search,
      orderby: "title",
      order: "asc",
    };

    const requests = [
      apiFetch({
        path: addQueryArgs("/wc/store/v1/products", query),
      }),
    ];

    // Also fetch selected products to ensure they're included
    if (selected.length) {
      requests.push(
        apiFetch({
          path: addQueryArgs("/wc/store/v1/products", {
            catalog_visibility: "any",
            include: selected,
            per_page: 0,
          }),
        })
      );
    }

    Promise.all(requests)
      .then((results) => {
        const flatData = results.flat();
        const products = uniqBy(flatData, (item) => item.id).map((item) => ({
          id: item.id,
          name: decodeEntities(item.name),
        }));

        const newProductsMap = new Map();
        products.forEach((product) => {
          newProductsMap.set(product.id, product);
          newProductsMap.set(product.name, product);
        });

        setProductsList(products);
        setProductsMap(newProductsMap);
        setProductsLoaded(true);
      })
      .catch((error) => {
        console.error("Error fetching products:", error);
        setProductsLoaded(true);
      });
  }, [search, selected.join(",")]);

  return { productsMap, productsList, productsLoaded };
};

/**
 * Hook to fetch and manage products assigned to this thank you page
 */
const useAssignedProducts = (postId) => {
  const [assignedProductIds, setAssignedProductIds] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isUpdating, setIsUpdating] = useState(false);
  const [error, setError] = useState(null);
  const [notice, setNotice] = useState(null);

  useEffect(() => {
    if (!postId) {
      setIsLoading(false);
      return;
    }

    apiFetch({
      path: `/sparks/v1/thank-you/${postId}/products`,
    })
      .then((response) => {
        setAssignedProductIds(response.product_ids.map(String));
        setIsLoading(false);
      })
      .catch((err) => {
        console.error("Error loading products:", err);
        setError(err.message);
        setIsLoading(false);
      });
  }, [postId]);

  const updateProducts = useCallback(
    (productIds) => {
      setIsUpdating(true);
      if (!postId) return Promise.resolve();

      return apiFetch({
        path: `/sparks/v1/thank-you/${postId}/products`,
        method: "POST",
        data: {
          product_ids: productIds.map(Number),
        },
      })
        .then((response) => {
          setAssignedProductIds(response.product_ids.map(String));
          setNotice(
            __("Products updated successfully!", "sparks-for-woocommerce")
          );
          return response;
        })
        .catch((err) => {
          console.error("Error updating products:", err);
          setError(err.message);
          throw err;
        })
        .finally(() => {
          setTimeout(() => {
            setNotice(null);
          }, 3000);
          setIsUpdating(false);
        });
    },
    [postId]
  );

  return {
    assignedProductIds,
    isLoading,
    error,
    isUpdating,
    notice,
    updateProducts,
  };
};

const ProductSelect = () => {
  const postId = useSelect((select) =>
    select("core/editor").getCurrentPostId()
  );

  const [searchQuery, setSearchQuery] = useState("");
  const handleSearch = useDebounce(setSearchQuery, 300);

  const {
    assignedProductIds,
    isLoading,
    error,
    isUpdating,
    notice,
    updateProducts,
  } = useAssignedProducts(postId);

  const { productsMap, productsList, productsLoaded } = useProducts(
    searchQuery,
    assignedProductIds
  );

  const validSelectedProductIds = useMemo(() => {
    if (!assignedProductIds?.length || !productsMap.size)
      return assignedProductIds || [];
    return assignedProductIds.filter((id) => {
      const product = productsMap.get(Number(id));
      return !!product;
    });
  }, [assignedProductIds, productsMap]);

  const onTokenChange = useCallback(
    (values) => {
      const newProductIds = values.reduce((acc, nameOrId) => {
        const decodedNameOrId = isNaN(Number(nameOrId))
          ? decodeEntities(nameOrId)
          : Number(nameOrId);

        const product =
          productsMap.get(decodedNameOrId) ||
          productsMap.get(Number(decodedNameOrId));
        if (product) {
          acc.push(String(product.id));
        }
        return acc;
      }, []);

      updateProducts(newProductIds);
    },
    [productsMap, updateProducts]
  );

  const suggestions = useMemo(() => {
    return productsList
      .filter(
        (product) => !validSelectedProductIds?.includes(String(product.id))
      )
      .map((product) => decodeEntities(product.name));
  }, [productsList, validSelectedProductIds]);

  const transformTokenIntoProductName = useCallback(
    (token) => {
      const parsedToken = Number(token);

      if (Number.isNaN(parsedToken)) {
        return decodeEntities(token) || "";
      }

      const product = productsMap.get(parsedToken);
      return decodeEntities(product?.name || "");
    },
    [productsMap]
  );

  if (isLoading) {
    return <Loader />;
  }

  if (error) {
    return (
      <Notice status="error" isDismissible={false}>
        {__("Error loading products: ", "sparks-for-woocommerce")}
        {error}
      </Notice>
    );
  }

  return (
    <div>
      <div
        className={classNames({
          "opacity-50 pointer-events-none": isUpdating,
        })}
      >
        <FormTokenField
          displayTransform={transformTokenIntoProductName}
          label={__("Select Products", "sparks-for-woocommerce")}
          onChange={onTokenChange}
          onInputChange={handleSearch}
          suggestions={suggestions}
          __experimentalValidateInput={(value) => productsMap.has(value)}
          value={
            !productsLoaded
              ? [__("Loading…", "sparks-for-woocommerce")]
              : validSelectedProductIds || []
          }
          __experimentalExpandOnFocus={!isUpdating}
          __experimentalShowHowTo={false}
        />
      </div>

      {isUpdating && <Loader />}

      {notice && (
        <Notice status="success" isDismissible={false}>
          {notice}
        </Notice>
      )}

      <div className="text-xs text-gray-600 grid gap-1">
        <hr className="my-1" />

        <span>
          {__(
            "This control will save automatically when changed.",
            "sparks-for-woocommerce"
          )}
        </span>

        <span>
          {createInterpolateElement(
            __(
              "<strong>Note:</strong> A product can only be assigned to one thank you page at a time.",
              "sparks-for-woocommerce"
            ),
            {
              strong: <strong></strong>,
            }
          )}
        </span>
      </div>
    </div>
  );
};

export default ProductSelect;
