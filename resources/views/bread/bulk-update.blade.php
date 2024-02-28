@php
    $edit = true;
    $add  = false;
    $bulkUpdateDataTypeContent = new $dataType->model_name();
    removeRelationshipField($dataType, 'add');
    removeRelationshipField($dataType, 'edit');
    $dataTypeDataRows = \bulkUpdateRows($dataType);
    $dataTypeDataRows = method_exists($action, 'rows') ? $action->rows() : $dataTypeDataRows;
    $hash = md5(get_class($action) . json_encode($dataTypeDataRows));
@endphp

<!-- <form method="post" action="{{ route('voyager.'.$dataType->slug.'.action') }}" style="display:inline">
    {{ csrf_field() }}
    <button type="submit" {!! $action->convertAttributesToHtml() !!}><i class="{{ $action->getIcon() }}"></i>  <span class="hidden-xs hidden-sm">{{ $action->getTitle() }}</span></button>
    <input type="hidden" name="action" value="{{ get_class($action) }}">
    <input type="hidden" name="ids" value="" class="selected_ids">
</form> -->
<a class="btn btn-info" id="bulk_update_btn{{ $hash }}" {!! $action->convertAttributesToHtml() !!}><i class="{{ $action->getIcon() }}"></i> <span>{{ $action->getTitle() }}</span></a>

{{-- Bulk bulk update modal --}}
<div class="modal modal-info fade" tabindex="-1" id="bulk_update_modal{{ $hash }}" role="dialog">
    <form action="{{ route('voyager.'.$dataType->slug.'.bulk-update') }}" id="bulk_update_form{{ $hash }}" method="POST" enctype="multipart/form-data">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <i class="voyager-edit"></i> {{ __('joy-voyager-bulk-update::generic.bulk_update_title') }} <span id="bulk_update_count{{ $hash }}"></span> <span id="bulk_update_display_name{{ $hash }}"></span>
                </h4>
            </div>
            <div class="modal-body" id="bulk_update_modal_body{{ $hash }}">
                {{ csrf_field() }}
                <!-- Adding / Editing -->
                @php
                    $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )}->filter(function($row) use($dataTypeDataRows) {
                        return in_array($row->field, $dataTypeDataRows) || (
                            $row->type === 'relationship' && in_array($row->details->column, $dataTypeDataRows)
                        );
                    });
                @endphp

                @foreach($dataTypeRows as $row)
                    <!-- GET THE DISPLAY OPTIONS -->
                    @php
                        $display_options = $row->details->display ?? NULL;
                        if ($bulkUpdateDataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                            $bulkUpdateDataTypeContent->{$row->field} = $bulkUpdateDataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                        } elseif ($bulkUpdateDataTypeContent->{$row->field.'_'.($edit ? 'bulk_edit' : 'bulk_add')}) {
                            $bulkUpdateDataTypeContent->{$row->field} = $bulkUpdateDataTypeContent->{$row->field.'_'.($edit ? 'bulk_edit' : 'bulk_add')};
                        }
                    @endphp
                    @if (isset($row->details->legend) && isset($row->details->legend->text))
                        <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                    @endif

                    <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif>
                        {{ $row->slugify }}
                        <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                        @include('voyager::multilingual.input-hidden-bread-edit-add', ['dataTypeContent' => $bulkUpdateDataTypeContent])
                        @if (isset($row->details->view))
                            @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $bulkUpdateDataTypeContent, 'content' => $bulkUpdateDataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                        @elseif ($row->type == 'relationship')
                            @include('voyager::formfields.relationship', ['options' => $row->details, 'dataTypeContent' => $bulkUpdateDataTypeContent])
                        @else
                            {!! app('voyager')->formField($row, $dataType, $bulkUpdateDataTypeContent) !!}
                        @endif

                        @foreach (app('voyager')->afterFormFields($row, $dataType, $bulkUpdateDataTypeContent) as $after)
                            {!! $after->handle($row, $dataType, $bulkUpdateDataTypeContent) !!}
                        @endforeach
                        @if ($errors->has($row->field))
                            @foreach ($errors->get($row->field) as $error)
                                <span class="help-block">{{ $error }}</span>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <!-- <button type="submit" {!! $action->convertAttributesToHtml() !!}><i class="{{ $action->getIcon() }}"></i> <span class="hidden-xs hidden-sm">{{ $action->getTitle() }}</span></button> -->
                <input type="hidden" name="action" value="{{ get_class($action) }}">
                <input type="hidden" name="ids" id="bulk_update_input{{ $hash }}" value="">
                @foreach($dataTypeDataRows as $dataTypeDataRow)
                    <input type="hidden" name="rows[]" value="{{ $dataTypeDataRow }}">
                @endforeach
                <input type="submit" class="btn btn-info pull-right bulk-update-confirm"
                            value="{{ __('joy-voyager-bulk-update::generic.bulk_update_confirm') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_plural')) }}">
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">
                    {{ __('voyager::generic.cancel') }}
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
    </form>
</div><!-- /.modal -->

@push('javascript')
<script>
$(function() {
    // Bulk bulk update selectors
    var $bulkUpdateBtn = $('#bulk_update_btn{{ $hash }}');
    var $bulkUpdateModal = $('#bulk_update_modal{{ $hash }}');
    var $bulkUpdateCount = $('#bulk_update_count{{ $hash }}');
    var $bulkUpdateDisplayName = $('#bulk_update_display_name{{ $hash }}');
    var $bulkUpdateInput = $('#bulk_update_input{{ $hash }}');
    // Reposition modal to prevent z-index issues
    $bulkUpdateModal.appendTo('body');
    // Bulk bulk update listener
    $bulkUpdateBtn.click(function () {
        var ids = [];
        var $checkedBoxes = $('#dataTable input[type=checkbox]:checked').not('.select_all');

        // Assuming all checkboxes are under a table with id 'dataTable'
        var checkboxes = $('#dataTable input[type=checkbox]');
        
        var statusColumnIndex = -1;
        var allCheckboxesHaveSameStatus = true;
        
        // Find the index of the column containing the header text "Status"
        $('#dataTable thead th').each(function(index) {
            if ($(this).text().trim().toLowerCase() === 'status') {
                statusColumnIndex = index;
                return false; // exit the loop once status column is found
            }
        });
        if (statusColumnIndex !== -1) {
            var firstStatus = $('#dataTable tbody tr input[type=checkbox]:checked').first().closest('tr').find('td:eq(' + statusColumnIndex + ')').text().trim();
            checkboxes.each(function() {
                if ($(this).is(':checked')) {
                    var currentStatus = $(this).closest('tr').find('td:eq(' + statusColumnIndex + ')').text().trim();
                    if (currentStatus !== firstStatus) {
                        allCheckboxesHaveSameStatus = false;
                        return false; // exit the loop early if status is different
                    }
                }
            });
        } else {
            allCheckboxesHaveSameStatus = false;
        }
        
        var count = $checkedBoxes.length;
        if (count) {
            if(statusColumnIndex !== -1 && !allCheckboxesHaveSameStatus) {
                toastr.warning('Not all checkbox rows have the same status.');
            } else {
                // Reset input value
                $bulkUpdateInput.val('');
                // Deletion info
                var displayName = count > 1 ? '{{ $dataType->getTranslatedAttribute('display_name_plural') }}' : '{{ $dataType->getTranslatedAttribute('display_name_singular') }}';
                displayName = displayName.toLowerCase();
                $bulkUpdateCount.html(count);
                $bulkUpdateDisplayName.html(displayName);
                // Gather IDs
                $.each($checkedBoxes, function () {
                    var value = $(this).val();
                    ids.push(value);
                })
                // Set input value
                $bulkUpdateInput.val(ids);

                // Show modal
                $bulkUpdateModal.modal('show');
            }
        } else {
            // No row selected
            toastr.warning('{{ __('joy-voyager-bulk-update::generic.bulk_update_nothing') }}');
        }
    });
});
</script>
@endpush