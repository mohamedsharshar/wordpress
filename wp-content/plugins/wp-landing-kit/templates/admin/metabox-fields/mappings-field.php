<?php
/**
 * @var Domain $domain
 */

use WpLandingKit\Models\Domain;

$data = isset( $data ) ? $data : new stdClass();
$domain = isset( $data['domain'] ) ? $data['domain'] : null;

if ( ! $domain instanceof Domain ) {
	return;
}

/**
 * Mapping structures
 *
 * Single post:
 * Single page: index.php?page_id=21
 * Single CPT: index.php?p=66&post_type=my-test-type
 * Post type archive:
 * Taxonomy term archive:
 */

?>
<div class="WplkField">
	<div class="WplkField__label">
		<label for="mapped-domain-post-id">Mappings</label>
	</div>

	<div class="WplkField__field">

		<div class="WplkMappings">
			<div class="WplkMapping WplkMapping--not-sortable">
				ROOT MAPPING HERE
			</div>
			<div class="WplkMapping WplkMapping--not-sortable">
				<div class="WplkMapping__preview">
					preview here
				</div>
				<div class="WplkMapping__settings">
					settings here
				</div>
			</div>
		</div>

		<table class="wplk-mappings-table">
			<tr class="wplk-mappings-table-headers wplk-mappings-table--not-sortable">
				<th style="text-align:left; border-bottom: solid 2px #E1E1E1; padding: 0 0 6px;">Type</th>
				<th style="text-align:left; border-bottom: solid 2px #E1E1E1; padding: 0 0 6px;">URL</th>
				<th style="text-align:left; border-bottom: solid 2px #E1E1E1; padding: 0 0 6px;">Action</th>
				<th style="text-align:left; padding: 0 0 6px;"></th>
			</tr>
			<tr class="wplk-mappings-table-root wplk-mappings-table--not-sortable">
				<td>
					Domain root
				</td>
				<td>
					http://mapped-domain-2.test/
				</td>
				<td>
					<div>
						Map to: <br>
						/some/page/mapping/here
					</div>
					<button><span class="dashicons dashicons-edit"></span></button>
					<!--                    <select name="" id="" style="display:inline; width: auto;">-->
					<!--                        <option value="">Single post</option>-->
					<!--                        <option value="">Post type archive</option>-->
					<!--                        <option value="">Taxonomy term archive</option>-->
					<!--                    </select>-->
					<!--                    <select name="" id="" style="display:inline; width: auto;">-->
					<!--                        <option value="">Post type A</option>-->
					<!--                        <option value="">Post type B</option>-->
					<!--                    </select>-->
				</td>
				<td>
					<?php // no actions available here ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php // nothing here ?>
				</td>
				<td>
					http://mapped-domain-2.test/ <input type="text">
				</td>
				<td>
					TODO: Actions
				</td>
				<td>
					<button type="button" class="wplk-add-mapping-btn button-secondary"
					        style="font-size:16px; line-height:1; vertical-align: middle;">+
					</button>
					<button type="button" class="wplk-remove-mapping-btn button-secondary"
					        style="font-size:16px; line-height:1; vertical-align: middle;">–
					</button>
				</td>
			</tr>
			<tr class="wplk-mappings-table-fallback wplk-mappings-table--not-sortable">
				<td>
					Fallback
				</td>
				<td>
					http://mapped-domain-2.test/.+
				</td>
				<td>
					TODO: Actions
				</td>
				<td>
					<?php // no actions available here ?>
				</td>
			</tr>
		</table>

		<style>
			.wplk-mappings-table-placeholder {
				background: #ccc;
			}

			.wplk-mappings-table-placeholder td {
				height: 30px;
			}
		</style>

		<script>
            (function ($, window, document, undefined) {
                $(function () {

                    $('.wplk-mappings-table')
                        .sortable({
                            items: "tr:not(.wplk-mappings-table--not-sortable)",
                            placeholder: "wplk-mappings-table-placeholder",
                        })
                        .on('click', 'button', function (e) {
                            e.preventDefault();
                            var $btn = $(e.target);
                            var $row = $btn.closest('tr');

                            if ($btn.hasClass('wplk-add-mapping-btn')) {
                                $row.clone().insertAfter($row);
                            }

                            if ($btn.hasClass('wplk-remove-mapping-btn')) {
                                $row.remove();
                            }

                        });

                });
            })(jQuery, window, document);
		</script>

	</div>
</div>