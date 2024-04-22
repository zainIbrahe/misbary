@php
    $edit = !is_null($dataTypeContent->getKey());
    $add = is_null($dataTypeContent->getKey());
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.' . ($edit ? 'edit' : 'add')) . ' ' .
    $dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.' . ($edit ? 'edit' : 'add')) . ' ' . $dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <style>
        .row {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            flex-wrap: wrap;
        }

        .row>[class*='col-'] {
            display: flex;
            flex-direction: column;
        }
    </style>
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form" class="form-edit-add"
                        action="{{ $edit ? route('voyager.' . $dataType->slug . '.update', $dataTypeContent->getKey()) : route('voyager.' . $dataType->slug . '.store') }}"
                        method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if ($edit)
                            {{ method_field('PUT') }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="panel-body">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{$edit ? 'editRows' : 'addRows'};
                            @endphp

                            @foreach ($dataTypeRows as $row)
                                <!-- GET THE DISPLAY OPTIONS -->
							
								
                                @php
                                    $display_options = $row->details->display ?? null;
                                    if ($dataTypeContent->{$row->field . '_' . ($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field . '_' . ($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}"
                                        style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">
                                        {{ $row->details->legend->text }}</legend>
                                @endif

                                <div class="form-group @if ($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}"
                                    @if (isset($display_options->id)) {{ "id=$display_options->id" }} @endif>
                                    {{ $row->slugify }}
                                    <label class="control-label"
                                        for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if ($add && isset($row->details->view_add))
									
                                        @include($row->details->view_add, [
                                            'row' => $row,
                                            'dataType' => $dataType,
                                            'dataTypeContent' => $dataTypeContent,
                                            'content' => $dataTypeContent->{$row->field},
                                            'view' => 'add',
                                            'options' => $row->details,
                                        ])
                                    @elseif ($edit && isset($row->details->view_edit))
									
                                        @include($row->details->view_edit, [
                                            'row' => $row,
                                            'dataType' => $dataType,
                                            'dataTypeContent' => $dataTypeContent,
                                            'content' => $dataTypeContent->{$row->field},
                                            'view' => 'edit',
                                            'options' => $row->details,
                                        ])
                                    @elseif (isset($row->details->view))
									
                                        @include($row->details->view, [
                                            'row' => $row,
                                            'dataType' => $dataType,
                                            'dataTypeContent' => $dataTypeContent,
                                            'content' => $dataTypeContent->{$row->field},
                                            'action' => $edit ? 'edit' : 'add',
                                            'view' => $edit ? 'edit' : 'add',
                                            'options' => $row->details,
                                        ])
                                    @elseif ($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', [
                                            'options' => $row->details,
                                        ])
                                    @else
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @endif

                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach
							
							
							<div class="form-group col-md-12 ">
								<label class="control-label"
                                        for="currency">Currency</label>
								<select name="currency" class="form-control">
									@foreach(json_decode($currencies) as $cur)
										<option @if(count($dataTypeContent->sku) > 0 && $dataTypeContent->sku[0]->currency == $cur->cc ) selected @endif value="{{$cur->cc}}">{{$cur->cc}}</option>
									@endforeach
								</select>
							</div>
							

                            <div class="attribute-values">
                                <div class="container">
										@if(count($dataTypeContent->sku) > 0)
										
										@foreach($dataTypeContent->sku[0]->attributes as $at)
										@if( $at->attribute_type != 226)
											 <div class="row">
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Attribute Type
                                                </label>
                                                <select  class="form-control changeAttr" name="types[]">`;
													@foreach($attributes as $att)
														<option {{$att->id == $at->attribute_type ? "selected" : ""}} value="{{$att->id}}">{{$att->name}}</option>
													@endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                          
                                        </div>`;
		
		                 <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Attribute Value
                                                </label>
                                                <select  name="values[]" class="attVals form-control">
                                                    <option value="{{$at->attribute_value}}">
                                                        {{$at->attribute_value}}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <span class="icon voyager-trash deleteLine"
                                                style="font-size:20px;color:red;cursor: pointer;"></span>
                                        </div>
                                    </div>
									@endif
										@endforeach
										 <div class="row">
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Attribute Type
                                                </label>
                                                <select  class="form-control changeAttr" name="types[]">`;
													@foreach($attributes as $att)
														<option {{$att->id == 226 ? "selected" : ""}} value="{{$att->id}}">{{$att->name}}</option>
													@endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                        </div>`;
		
		                 <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Attribute Value
                                                </label>
                                                <select  multiple name="values[]" class="attVals form-control">
													@foreach($dataTypeContent->sku[0]->attributes as $at)
													@if($at->attribute_type == 226)
														<option selected value="{{$at->attribute_value}}">
                                                        	{{$at->attribute_value}}
                                                    	</option>
													@endif
													@endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <span class="icon voyager-trash deleteLine"
                                                style="font-size:20px;color:red;cursor: pointer;"></span>
                                        </div>
                                    </div>
									@endif
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary add-attr mt-4">Add Attribute</button>



                            <div class="pros-values">
                                <div class="container">
									@if(count($dataTypeContent->sku) > 0)
									
										@foreach($dataTypeContent->sku[0]->pro as $pro)
									@if($pro->type == "pros" || $pro->type == "cons" || $pro->type == "whatsnew")
											<div class="row">
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Compare Type
                                                </label>
                                                <select class="form-control" name="compareTypes[]">
                                                    <option {{ $pro->type == "pros" ? "selected" : ""}} value="pros">Pros</option>
                                                    <option {{ $pro->type == "cons" ? "selected" : ""}} value="cons">Cons</option>
													<option {{ $pro->type == "whatsnew" ? "selected" : ""}} value="whatsnew">Whats New</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Compare Description
                                                </label>
                                                <input type="text" value={{$pro->description}} class="form-control" name="compareValues[]" id="">
                                            </div>
                                        </div>
                                        <div class="col-md-2"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <span class="icon voyager-trash deleteLine"
                                                style="font-size:20px;color:red;cursor: pointer;"></span>
                                        </div>
                                    </div>
											@endif
										@endforeach
									@endif
                            </div>
                            <button type="button" class="btn btn-primary add-pros mt-4">Add Features</button>
                        </div><!-- panel-body -->

                        <div class="panel-footer">
                        @section('submit-buttons')
                            <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                        @stop
                        @yield('submit-buttons')
                    </div>
                </form>

                <div style="display:none">
                    <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                    <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-danger" id="confirm_delete_modal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
            </div>

            <div class="modal-body">
                <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                    data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                <button type="button" class="btn btn-danger"
                    id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
            </div>
        </div>
    </div>
</div>
<!-- End Delete File Modal -->
@stop

@section('javascript')
<script>
    var params = {};
    var $file;

    $('.add-attr').click(function() {
        $attributeElement = $(".attribute-values .container");
		var attrs = @json($attributes);
		
        let attributeLiine = `
        						<div class="row">
                                        
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Attribute Type
                                                </label>
                                                <select class="form-control changeAttr" name="types[]">`;
		for(v in attrs){
			attributeLiine += `<option value="`+attrs[v].id+`">`+attrs[v].name+`</option>`;										  
		}
		attributeLiine += `
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                          
                                        </div>`;
		
		
                      attributeLiine += `<div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Attribute Value
                                                </label>
                                                <select name="values[]" class="attVals form-control">
                                                    <option value="">
                                                        Choose Value
                                                    </options>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <span class="icon voyager-trash deleteLine"
                                                style="font-size:20px;color:red;cursor: pointer;"></span>
                                        </div>
                                    </div>
        `;
        $attributeElement.append(attributeLiine);
    })

    $('.add-pros').click(function() {
        $attributeElement = $(".pros-values .container");
        let attributeLiine = `
        <div class="row">
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Compare Type
                                                </label>
                                                <select class="form-control" name="compareTypes[]">
                                                    <option value="pros">Pros</option>
                                                    <option value="cons">Cons</option>
													<option value="whatsnew">Whats New</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            
                                        </div>
                                        <div class="col-md-3"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <div class="form-group" style="width:100%">
                                                <label for="">
                                                    Compare Description
                                                </label>
                                                <input type="text" class="form-control" name="compareValues[]" id="">
                                            </div>
                                        </div>
                                        <div class="col-md-2"
                                            style="justify-content: center;align-items: center;margin-bottom: 0px">
                                            <span class="icon voyager-trash deleteLine"
                                                style="font-size:20px;color:red;cursor: pointer;"></span>
                                        </div>
                                    </div>
        `;
        $attributeElement.append(attributeLiine);
    })
    $(document).on('click', '.deleteLine', function() {
        $(this).parent().parent().remove();
    });
    $(document).on('change', '.changeAttr', function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });
        attrElement = $(this);
		var vals = this.value;
	
        $.ajax({
            type: "GET",
            dataType: "json",
            url: '/api/getAttrValues/' + this.value,
            success: function(response) {
                
                $result = $(attrElement).parent().parent().next().next().find(
                    '.form-group .attVals');
                $result.html('');
				if(response[0].type == "text"){
					$result.remove();
					$(attrElement).parent().parent().next().next().html(`   <div class="form-group" style="width:100%">
					<label for="">
                                                    Attribute Value
                                                </label>
												<input type='text' name='values[]' class='form-control attVals' /></div>`);
				}
				else{
					$(attrElement).parent().parent().next().next().html("");
					$(attrElement).parent().parent().next().next().append(`   <div class="form-group" style="width:100%">  <label for="">
                                                    Attribute Value
                                                </label>
                                                <select name="values[]" class="attVals form-control">
                                                    <option value="">
                                                        Choose Value
                                                    </options>
                                                </select></div>`);
					 $result = $(attrElement).parent().parent().next().next().find(
                    '.form-group .attVals');
					response[1].forEach(element => {
						 $result = $(attrElement).parent().parent().next().next().find(
                    '.form-group .attVals');
						
                    $result.append(
							`<option value="` + element.en_name + `">` + element.en_name +
							`</option>`
						);
					});
					
						if(vals == "226"){
							console.log(vals);
							$(attrElement).parent().parent().next().next().find(
									'.form-group .attVals').attr("multiple","multiple");
						}
						else{
							$(attrElement).parent().parent().next().next().find(
									'.form-group .attVals').removeAttr("multiple")
						}
				}
                
            }
        })
    })

    function deleteHandler(tag, isMulti) {
        return function() {
            $file = $(this).siblings(tag);

            params = {
                slug: '{{ $dataType->slug }}',
                filename: $file.data('file-name'),
                id: $file.data('id'),
                field: $file.parent().data('field-name'),
                multi: isMulti,
                _token: '{{ csrf_token() }}'
            }

            $('.confirm_delete_name').text(params.filename);
            $('#confirm_delete_modal').modal('show');
        };
    }

    $('document').ready(function() {
        $('.toggleswitch').bootstrapToggle();

        //Init datepicker for date fields if data-datepicker attribute defined
        //or if browser does not handle date inputs
        $('.form-group input[type=date]').each(function(idx, elt) {
            if (elt.hasAttribute('data-datepicker')) {
                elt.type = 'text';
                $(elt).datetimepicker($(elt).data('datepicker'));
            } else if (elt.type != 'date') {
                elt.type = 'text';
                $(elt).datetimepicker({
                    format: 'L',
                    extraFormats: ['YYYY-MM-DD']
                }).datetimepicker($(elt).data('datepicker'));
            }
        });

        @if ($isModelTranslatable)
            $('.side-body').multilingual({
                "editing": true
            });
        @endif

        $('.side-body input[data-slug-origin]').each(function(i, el) {
            $(el).slugify();
        });

        $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
        $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
        $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
        $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

        $('#confirm_delete').on('click', function() {
            $.post('{{ route('voyager.' . $dataType->slug . '.media.remove') }}', params, function(
                response) {
                if (response &&
                    response.data &&
                    response.data.status &&
                    response.data.status == 200) {

                    toastr.success(response.data.message);
                    $file.parent().fadeOut(300, function() {
                        $(this).remove();
                    })
                } else {
                    toastr.error("Error removing file.");
                }
            });

            $('#confirm_delete_modal').modal('hide');
        });
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@stop
