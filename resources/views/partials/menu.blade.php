<div class="sidebar">
    <nav class="sidebar-nav">

        <ul class="nav">
            <li class="nav-item">
                <a href="{{ route("admin.home") }}" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt">

                    </i>
                    {{ trans('global.dashboard') }}
                </a>
            </li>         

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="fas fa-dolly-flatbed nav-icon">

                    </i>
                    {{ trans('global.productInventory.title') }}
                </a>
                <ul class="nav-dropdown-items">   

                @can('order_access')             
                <li class="nav-item">
                    <a href="{{ route("admin.orders.index") }}" class="nav-link {{ request()->is('admin/orders') || request()->is('admin/orders/*') ? 'active' : '' }}">
                        <i class="fas fa-cart-arrow-down nav-icon">

                        </i>
                        {{ trans('global.order.title') }}
                    </a>
                </li>
                @endcan

                @can('saleretur_access')
                <li class="nav-item">
                    <a href="{{ route("admin.salereturs.index") }}" class="nav-link {{ request()->is('admin/salereturs') || request()->is('admin/salereturs/*') ? 'active' : '' }}">
                        <i class="fas fa-chevron-circle-left nav-icon">

                        </i>
                        {{ trans('global.saleretur.title') }}
                    </a>
                </li>
                @endcan

                @can('production_access')
                <li class="nav-item">
                    <a href="{{ route("admin.productions.index") }}" class="nav-link {{ request()->is('admin/productions') || request()->is('admin/productions/*') ? 'active' : '' }}">
                        <i class="fas fa-luggage-cart nav-icon">

                        </i>
                        {{ trans('global.production.title') }}
                    </a>
                </li>
                @endcan

                @can('product_access')
                <li class="nav-item">
                    <a href="{{ route("admin.products.index") }}" class="nav-link {{ request()->is('admin/products') || request()->is('admin/products/*') ? 'active' : '' }}">
                        <i class="fas fa-cogs nav-icon">

                        </i>
                        {{ trans('global.product.title') }}
                    </a>
                </li>
                @endcan

                @can('package_access')
                <li class="nav-item">
                    <a href="{{ route("admin.packages.index") }}" class="nav-link {{ request()->is('admin/packages') || request()->is('admin/packages/*') ? 'active' : '' }}">
                        <i class="fas fa-cube nav-icon">

                        </i>
                        {{ trans('global.package.title') }}
                    </a>
                </li>   
                @endcan

                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="fas fa-project-diagram nav-icon">

                    </i>
                    {{ trans('global.network.title') }}
                </a>
                <ul class="nav-dropdown-items">

                @can('member_access')
                <li class="nav-item">
                    <a href="{{ route("admin.members.index") }}" class="nav-link {{ request()->is('admin/members') || request()->is('admin/members/*') ? 'active' : '' }}">
                        <i class="fas fa-link nav-icon">

                        </i>
                        {{ trans('global.network.title_member') }}
                    </a>
                </li>
                @endcan
                
                @can('career_access')
                <li class="nav-item">
                    <a href="{{ route("admin.careers.index") }}" class="nav-link {{ request()->is('admin/careers') || request()->is('admin/careers/*') ? 'active' : '' }}">
                        <i class="fas fa-users-cog nav-icon">

                        </i>
                        {{ trans('global.career.title') }}
                    </a>
                </li>
                @endcan

                @can('careertype_access')
                <li class="nav-item">
                    <a href="{{ route("admin.careertypes.index") }}" class="nav-link {{ request()->is('admin/careertypes') || request()->is('admin/careertypes/*') ? 'active' : '' }}">
                        <i class="fas fa-users-cog nav-icon">

                        </i>
                        {{ trans('global.careertype.title') }}
                    </a>
                </li>
                @endcan

                @can('martregister_access')
                <li class="nav-item">
                    <a href="{{ route("admin.martregisters.index") }}" class="nav-link {{ request()->is('admin/martregisters') || request()->is('admin/martregisters/*') ? 'active' : '' }}">
                        <i class="fas fa-users-cog nav-icon">

                        </i>
                        {{ trans('global.martregister.title') }}
                    </a>
                </li>
                @endcan

                @can('agent_access')
                <li class="nav-item">
                    <a href="{{ route("admin.agents.index") }}" class="nav-link {{ request()->is('admin/agents') || request()->is('admin/agents/*') ? 'active' : '' }}">
                        <i class="fas fa-users-cog nav-icon">

                        </i>
                        {{ trans('global.agent.title') }}
                    </a>
                </li>
                @endcan

                @can('agent_access')
                <li class="nav-item">
                    <a href="{{ route("admin.agents.buyRecap") }}" class="nav-link {{ request()->is('admin/agents') || request()->is('admin/agents/*') ? 'active' : '' }}">
                        <i class="fas fa-cart-arrow-down nav-icon">

                        </i>
                        Pembelian Agen
                    </a>
                </li>
                @endcan

                @can('agent_access')
                <li class="nav-item">
                    <a href="{{ route("admin.agents.saleRecap") }}" class="nav-link {{ request()->is('admin/agents') || request()->is('admin/agents/*') ? 'active' : '' }}">
                        <i class="fas fa-luggage-cart nav-icon">

                        </i>
                        Penjualan Agen
                    </a>
                </li>
                @endcan

                @can('networkfee_access_access')
                <li class="nav-item">
                    <a href="{{ route("admin.members.index") }}" class="nav-link {{ request()->is('admin/members') || request()->is('admin/members/*') ? 'active' : '' }}">
                        <i class="fas fa-link nav-icon">

                        </i>
                        {{ trans('global.network.title_member') }}
                    </a>
                </li>  
                @endcan

                @can('topup_access')
                <li class="nav-item">
                    <a href="{{ route("admin.topups.index") }}" class="nav-link {{ request()->is('admin/topups') || request()->is('admin/topups/*') ? 'active' : '' }}">
                        <i class="fas fa-donate nav-icon">

                        </i>
                        {{ trans('global.topup.title') }}
                    </a>
                </li> 
                @endcan

                @can('transfer_access')
                <li class="nav-item">
                    <a href="{{ route("admin.transfers.index") }}" class="nav-link {{ request()->is('admin/transfers') || request()->is('admin/transfers/*') ? 'active' : '' }}">
                        <i class="fas fa-arrow-up nav-icon">

                        </i>
                        {{ trans('global.transfer.title') }}
                    </a>
                </li> 
                @endcan

                @can('withdraw_access')
                <li class="nav-item">
                    <a href="{{ route("admin.withdraw.index") }}" class="nav-link {{ request()->is('admin/withdraw') || request()->is('admin/withdraw/*') ? 'active' : '' }}">
                        <i class="fas fa-exchange-alt nav-icon">

                        </i>
                        {{ trans('global.withdraw.title') }}
                    </a>
                </li>
                @endcan

                @can('networkfee_access')
                <li class="nav-item">
                    <a href="{{ route("admin.fees.index") }}" class="nav-link {{ request()->is('admin/fees') || request()->is('admin/fees/*') ? 'active' : '' }}">
                        <i class="fas fa-gift nav-icon">

                        </i>
                        {{ trans('global.networkfee.title') }}
                    </a>
                </li> 
                @endcan

                @can('orderpoint_access')
                <li class="nav-item">
                        <a href="{{ route("admin.history-points") }}" class="nav-link {{ request()->is('admin/history-points') || request()->is('admin/history-points/*') ? 'active' : '' }}">
                            <i class="fas fa-file-powerpoint nav-icon">

                            </i>
                            {{ trans('global.orderpoint.title') }}
                        </a>
                    </li>  
                    @endcan

                    @can('networkfee_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.members.pairingPending") }}" class="nav-link {{ request()->is('admin/pairing-pending') || request()->is('admin/pairing-pending/*') ? 'active' : '' }}">
                            <i class="fas fa-file-powerpoint nav-icon">

                            </i>
                            {{ trans('global.pairingpending.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('activation_type_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.activation-type.index") }}" class="nav-link {{ request()->is('admin/activation-type') || request()->is('admin/activation-type/*') ? 'active' : '' }}">
                            <i class="fas fa-toggle-on nav-icon">

                            </i>
                            {{ trans('global.activation_type.title') }}
                        </a>
                    </li>  
                    @endcan

                    @can('accountlock_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.accountlocks.index") }}" class="nav-link {{ request()->is('admin/accountlocks') || request()->is('admin/accountlocks/*') ? 'active' : '' }}">
                            <i class="fas fa-lock nav-icon">

                            </i>
                            {{ trans('global.accountlock.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('setting_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.settings.index") }}" class="nav-link {{ request()->is('admin/settings') || request()->is('admin/settings/*') ? 'active' : '' }}">
                            <i class="fas fa-check nav-icon">

                            </i>
                            {{ trans('global.setting.title') }}
                        </a>
                    </li>  
                    @endcan

                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="far fa-credit-card nav-icon">

                    </i>
                    {{ trans('global.payreceivable.title') }}
                </a>
                <ul class="nav-dropdown-items">    
                    
                    @can('payable_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.payables.index") }}" class="nav-link {{ request()->is('admin/payables') || request()->is('admin/payables/*') ? 'active' : '' }}">
                            <i class="far fa-calendar-minus nav-icon">

                            </i>
                            {{ trans('global.payable.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('receivable_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.receivables.index") }}" class="nav-link {{ request()->is('admin/receivables') || request()->is('admin/receivables/*') ? 'active' : '' }}">
                            <i class="far fa-calendar-plus nav-icon">

                            </i>
                            {{ trans('global.receivable.title') }}
                        </a>
                    </li>
                    @endcan

                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="fas fa-balance-scale nav-icon">

                    </i>
                    {{ trans('global.accountGroup.title') }}
                </a>
                <ul class="nav-dropdown-items">                    
                    
                @can('ledger_access')
                <li class="nav-item">
                        <a href="{{ route("admin.ledgers.index") }}" class="nav-link {{ request()->is('admin/ledgers') || request()->is('admin/ledgers/*') ? 'active' : '' }}">
                            <i class="fas fa-book nav-icon">

                            </i>
                            {{ trans('global.ledger.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('account_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.accbalance") }}" class="nav-link {{ request()->is('admin/accbalance') || request()->is('admin/accbalance/*') ? 'active' : '' }}">
                            <i class="fas fa-clipboard-list nav-icon">

                            </i>
                            {{ trans('global.account.balance') }}
                        </a>
                    </li>
                    @endcan

                    @can('account_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.balancetrial") }}" class="nav-link {{ request()->is('admin/balancetrial') || request()->is('admin/balancetrial/*') ? 'active' : '' }}">
                            <i class="fas fa-percentage nav-icon">

                            </i>
                            {{ trans('global.account.balance_trial') }}
                        </a>
                    </li>
                    @endcan

                    @can('account_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.profitloss") }}" class="nav-link {{ request()->is('admin/profitloss') || request()->is('admin/profitloss/*') ? 'active' : '' }}">
                            <i class="fas fa-chart-line nav-icon">

                            </i>
                            {{ trans('global.account.profit_loss') }}
                        </a>
                    </li>
                    @endcan

                    @can('asset_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.assets.index") }}" class="nav-link {{ request()->is('admin/assets') || request()->is('admin/assets/*') ? 'active' : '' }}">
                            <i class="fas fa-building nav-icon">

                            </i>
                            {{ trans('global.asset.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('capital_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.capitals.index") }}" class="nav-link {{ request()->is('admin/capitals') || request()->is('admin/capitals/*') ? 'active' : '' }}">
                            <i class="fas fa-coins nav-icon">

                            </i>
                            {{ trans('global.capital.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('account_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.accounts.index") }}" class="nav-link {{ request()->is('admin/accounts') || request()->is('admin/accounts/*') ? 'active' : '' }}">
                            <i class="fas fa-list nav-icon">

                            </i>
                            {{ trans('global.account.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('account_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.accountsgroups.index") }}" class="nav-link {{ request()->is('admin/accountsgroups') || request()->is('admin/accountsgroups/*') ? 'active' : '' }}">
                            <i class="fas fa-layer-group nav-icon">

                            </i>
                            {{ trans('global.accountsgroup.title') }}
                        </a>
                    </li>    
                    @endcan

                    <!-- <li class="nav-item">
                        <a href="{{ route("admin.cogsallocats.index") }}" class="nav-link {{ request()->is('admin/cogsallocats') || request()->is('admin/cogsallocats/*') ? 'active' : '' }}">
                            <i class="fas fa-divide nav-icon">

                            </i>
                            {{ trans('global.cogsallocat.title') }}
                        </a>
                    </li>                -->
                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="fas fa-database nav-icon">

                    </i>
                    {{ trans('global.masterData.title') }}
                </a>
                <ul class="nav-dropdown-items">                    
                    
                @can('customer_access')
                <li class="nav-item">
                        <a href="{{ route("admin.customers.index") }}" class="nav-link {{ request()->is('admin/customers') || request()->is('admin/customers/*') ? 'active' : '' }}">
                            <i class="fas fa-users-cog nav-icon">

                            </i>
                            {{ trans('global.customer.title') }}
                        </a>
                    </li>
                    @endcan

                </ul>
                <ul class="nav-dropdown-items">                    
                    
                @can('capitalist_access')
                <li class="nav-item">
                        <a href="{{ route("admin.capitalists.index") }}" class="nav-link {{ request()->is('admin/capitalists') || request()->is('admin/capitalists/*') ? 'active' : '' }}">
                            <i class="fas fa-user-tie nav-icon">

                            </i>
                            {{ trans('global.capitalist.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('clinic_access')
                <li class="nav-item">
                        <a href="{{ route("admin.clinics.index") }}" class="nav-link {{ request()->is('admin/clinics') || request()->is('admin/clinics/*') ? 'active' : '' }}">
                            <i class="fas fa-users-cog nav-icon">

                            </i>
                            {{ trans('global.clinic.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('expert_access')
                <li class="nav-item">
                        <a href="{{ route("admin.experts.index") }}" class="nav-link {{ request()->is('admin/experts') || request()->is('admin/experts/*') ? 'active' : '' }}">
                            <i class="fas fa-users-cog nav-icon">

                            </i>
                            {{ trans('global.expert.title') }}
                        </a>
                    </li>
                    @endcan

                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="fas fa-chart-line nav-icon"></i>

                    </i>
                    {{ trans('global.statistic.title') }}
                </a>
                <ul class="nav-dropdown-items">                    
                
                @can('statistic_access')
                <li class="nav-item">
                        <a href="{{ route("admin.statistik.index") }}" class="nav-link">
                            <i class="fas fa-chart-bar nav-icon"></i>
                            {{ trans('global.statistic.fields.omset_global') }}
                        </a>
                    </li>   
                    @endcan

                </ul>
                <ul class="nav-dropdown-items"> 
                    
                    @can('statistic_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.statistik.product") }}" class="nav-link">
                            <i class="fas fa-chart-pie  nav-icon"></i>
                            {{ trans('global.statistic.fields.omset_product') }}
                        </a>
                    </li>  
                    @endcan

                </ul>
                <ul class="nav-dropdown-items">                    
                    
                @can('statistic_access')
                <li class="nav-item">
                        <a href="{{ route("admin.statistik.member") }}" class="nav-link">
                            <i class="fas fa-user nav-icon"></i>
                            {{ trans('global.statistic.fields.member') }}
                        </a>
                    </li>   
                    @endcan

                </ul>
                <ul class="nav-dropdown-items">                    
                    
                @can('statistic_access')
                <li class="nav-item">
                        <a href="{{ route("admin.statistik.memberOrder") }}" class="nav-link">
                            <i class="fas fa-chart-bar nav-icon"></i>
                            Statistic RO & AM
                        </a>
                    </li>   
                    @endcan

                </ul>
            </li>

            <li class="nav-item nav-dropdown">
                <a class="nav-link  nav-dropdown-toggle">
                    <i class="fas fa-users nav-icon">

                    </i>
                    {{ trans('global.userManagement.title') }}
                </a>
                <ul class="nav-dropdown-items">
                    
                @can('permission_access')
                <li class="nav-item">
                        <a href="{{ route("admin.permissions.index") }}" class="nav-link {{ request()->is('admin/permissions') || request()->is('admin/permissions/*') ? 'active' : '' }}">
                            <i class="fas fa-unlock-alt nav-icon">

                            </i>
                            {{ trans('global.permission.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('role_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.roles.index") }}" class="nav-link {{ request()->is('admin/roles') || request()->is('admin/roles/*') ? 'active' : '' }}">
                            <i class="fas fa-briefcase nav-icon">

                            </i>
                            {{ trans('global.role.title') }}
                        </a>
                    </li>
                    @endcan

                    @can('user_access')
                    <li class="nav-item">
                        <a href="{{ route("admin.users.index") }}" class="nav-link {{ request()->is('admin/users') || request()->is('admin/users/*') ? 'active' : '' }}">
                            <i class="fas fa-user nav-icon">

                            </i>
                            {{ trans('global.user.title') }}
                        </a>
                    </li>
                    @endcan

                </ul>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logoutform').submit();">
                    <i class="nav-icon fas fa-sign-out-alt">

                    </i>
                    {{ trans('global.logout') }}
                </a>
            </li>            
        </ul>

        <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
            <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
        </div>
        <div class="ps__rail-y" style="top: 0px; height: 869px; right: 0px;">
            <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 415px;"></div>
        </div>
    </nav>
    <button class="sidebar-minimizer brand-minimizer" type="button"></button>
</div>