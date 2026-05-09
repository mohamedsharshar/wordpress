import { useSelect, useDispatch } from '@wordpress/data';

const useMeta = () => {
  const { meta } = useSelect( ( select ) => ( {
    meta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
  } ) ); 

  const { editPost } = useDispatch( 'core/editor' );

  const getMetaValue = ( metaId ) => meta[ metaId ] || '';

  const setMetaValue = ( metaId, value ) => {
    editPost( {
      meta: { [ metaId ]: value },
    } );
  };

  return { getMetaValue, setMetaValue };
};

export default useMeta;