@extends('layout.app')

@section('meta')
@endsection

@section('title')
    View Cart
@endsection

@section('styles')
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card ">
                <div class="card-header ">
                    <h4 class="card-title">View Cart</h4>
                </div>
                <div class="card-body ">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <div class="card ">
                                <div class="card-header ">
                                    <h5 class="card-title">User Name</h5>
                                </div>
                                <div class="card-body" id="preview_user">
                                    <h6>{{ $data->user_name ?? '' }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="card ">
                                <div class="card-header ">
                                    <h5 class="card-title">Party Name</h5>
                                </div>
                                <div class="card-body" id="preview_party_name">
                                    <h6>{{ $data->party_name ?? '' }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="card ">
                                <div class="card-header ">
                                    <h5 class="card-title">Party Address</h5>
                                </div>
                                <div class="card-body" id="preview_party_address">
                                    <h6>{{ $data->party_address ?? '' }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="card ">
                                <div class="card-header ">
                                    <h5 class="card-title">Sub Users</h5>
                                </div>
                                <div class="card-body" id="preview_sub_users">
                                    @if($data->sub_users->isNotEmpty())
                                        @foreach($data->sub_users as $row)
                                            <h6>{{ $row->name ?? '' }}</h6>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="card ">
                                <div class="card-header ">
                                    <h5 class="card-title">Invenotories</h5>
                                </div>
                                <div class="card-body" id="preview_inventories">
                                    @if($data->inventories->isNotEmpty())
                                        @foreach($data->inventories as $row)
                                            <h6>{{ $row->name ?? '' }}</h6>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <div class="card ">
                                <div class="card-header ">
                                    <h5 class="card-title">Sub Items</h5>
                                </div>
                                <div class="card-body" id="preview_sub_items">
                                    @if($data->sub_items->isNotEmpty())
                                        @foreach($data->sub_items as $row)
                                            <div class="row">
                                                <div class="col-sm-6"><h6>{{ $row->name ?? '' }}</h6></div>
                                                <div class="col-sm-6"><h6>{{ $row->quantity ?? '' }}</h6></div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <a href="{{ route('cart') }}" class="btn btn-default">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection

