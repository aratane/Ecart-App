package wrteam.ecart.dboy.activity;

import static wrteam.ecart.dboy.helper.ApiConfig.disableSwipe;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.ActionBarDrawerToggle;
import androidx.appcompat.widget.Toolbar;
import androidx.core.widget.NestedScrollView;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.gms.tasks.OnSuccessListener;
import com.google.android.material.snackbar.Snackbar;
import com.google.firebase.messaging.FirebaseMessaging;
import com.google.gson.Gson;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

import wrteam.ecart.dboy.R;
import wrteam.ecart.dboy.adapter.OrderListAdapter;
import wrteam.ecart.dboy.helper.ApiConfig;
import wrteam.ecart.dboy.helper.AppController;
import wrteam.ecart.dboy.helper.Constant;
import wrteam.ecart.dboy.helper.Session;
import wrteam.ecart.dboy.helper.VolleyCallback;
import wrteam.ecart.dboy.model.OrderList;

public class MainActivity extends DrawerActivity {
    public static ArrayList<OrderList> orderListArrayList;
    public Session session;
    boolean doubleBackToExitPressedOnce = false;
    TextView tvOrdersCount, tvBalanceCount, tvBonusCount;
    RecyclerView recycleOrderList;
    Toolbar toolbar;
    Activity activity;
    OrderListAdapter orderListAdapter;
    SwipeRefreshLayout lyt_main_activity_swipe_refresh;
    NestedScrollView scrollView;
    int total = 0;
    private boolean isLoadMore = false;
    int offset = 0;
    Menu menu;

    @SuppressLint("SetTextI18n")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getLayoutInflater().inflate(R.layout.activity_main, frameLayout);
        toolbar = findViewById(R.id.toolbar);

        setSupportActionBar(toolbar);
        getSupportActionBar().setTitle(getString(R.string.app_name));
        getSupportActionBar().setDisplayHomeAsUpEnabled(false);
        activity = MainActivity.this;

        session = new Session(activity);

        tvBalanceCount = findViewById(R.id.tvBalanceCount);
        tvOrdersCount = findViewById(R.id.tvOrdersCount);
        tvBonusCount = findViewById(R.id.tvBonusCount);
        scrollView = findViewById(R.id.scrollView);

        recycleOrderList = findViewById(R.id.recycleOrderList);
        lyt_main_activity_swipe_refresh = findViewById(R.id.lyt_main_activity_swipe_refresh);

        if (session.isUserLoggedIn()) {
            GetData();
            getDeliveryBoyData(activity);
        }
        lyt_main_activity_swipe_refresh.setColorSchemeResources(R.color.colorPrimary);
        lyt_main_activity_swipe_refresh.setOnRefreshListener(new SwipeRefreshLayout.OnRefreshListener() {
            @Override
            public void onRefresh() {
                lyt_main_activity_swipe_refresh.setRefreshing(false);
                if (AppController.isConnected(activity)) {
                    offset = 0;
                    GetData();
                    getDeliveryBoyData(activity);
                    disableSwipe(lyt_main_activity_swipe_refresh);
                } else {
                    setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry));
                }

            }
        });

        drawerToggle = new ActionBarDrawerToggle
                (
                        this,
                        drawer, toolbar,
                        R.string.drawer_open,
                        R.string.drawer_close
                ) {
        };

        FirebaseMessaging.getInstance().getToken().addOnSuccessListener(new OnSuccessListener<String>() {
            @Override
            public void onSuccess(String token) {
                AppController.getInstance().setDeviceToken(token);
                updateFCMId();
            }
        });
    }

    public void updateFCMId() {
        Map<String, String> params = new HashMap<String, String>();
        params.put(Constant.ID, session.getData(Constant.ID));
        params.put(Constant.UPDATE_DELIVERY_BOY_FCM_ID, Constant.GetVal);
        params.put(Constant.FCM_ID, "" + AppController.getInstance().getDeviceToken());

        ApiConfig.RequestToVolley((result, response) -> {
            if(result){
                System.out.println(response);
            }
        }, activity, Constant.MAIN_URL, params, false);
    }


    private void GetData() {
        orderListArrayList = new ArrayList<>();
        final LinearLayoutManager linearLayoutManager = new LinearLayoutManager(activity);
        recycleOrderList.setLayoutManager(linearLayoutManager);
        Map<String, String> params = new HashMap<String, String>();
        params.put(Constant.ID, session.getData(Constant.ID));
        params.put(Constant.GET_ORDERS_BY_DELIVERY_BOY_ID, Constant.GetVal);
        params.put(Constant.OFFSET, "" + offset);
        params.put(Constant.LIMIT, Constant.PRODUCT_LOAD_LIMIT);
        ApiConfig.RequestToVolley(new VolleyCallback() {
            @Override
            public void onSuccess(boolean result, String response) {

                if (result) {
                    try {
                        //    System.out.println("====product  " + response);
                        JSONObject objectbject = new JSONObject(response);
                        if (!objectbject.getBoolean(Constant.ERROR)) {
                            total = Integer.parseInt(objectbject.getString(Constant.TOTAL));
                            session.setData(Constant.TOTAL, String.valueOf(total));

                            final JSONObject object = new JSONObject(response);
                            JSONArray jsonArray = object.getJSONArray(Constant.DATA);


                            Gson g = new Gson();


                            for (int i = 0; i < jsonArray.length(); i++) {
                                JSONObject jsonObject1 = jsonArray.getJSONObject(i);

                                if (jsonObject1 != null) {
                                    OrderList orderList = g.fromJson(jsonObject1.toString(), OrderList.class);
                                    orderListArrayList.add(orderList);
                                } else {
                                    break;
                                }

                            }
                            if (offset == 0) {
                                orderListAdapter = new OrderListAdapter(activity, orderListArrayList);
                                orderListAdapter.setHasStableIds(true);
                                recycleOrderList.setAdapter(orderListAdapter);
                                scrollView.setOnScrollChangeListener(new NestedScrollView.OnScrollChangeListener() {
                                    @Override
                                    public void onScrollChange(NestedScrollView v, int scrollX, int scrollY, int oldScrollX, int oldScrollY) {

                                        // if (diff == 0) {
                                        if (scrollY == (v.getChildAt(0).getMeasuredHeight() - v.getMeasuredHeight())) {
                                            LinearLayoutManager linearLayoutManager = (LinearLayoutManager) recycleOrderList.getLayoutManager();
                                            if (orderListArrayList.size() < total) {
                                                if (!isLoadMore) {
                                                    if (linearLayoutManager != null && linearLayoutManager.findLastCompletelyVisibleItemPosition() == orderListArrayList.size() - 1) {
                                                        //bottom of list!
                                                        orderListArrayList.add(null);
                                                        orderListAdapter.notifyItemInserted(orderListArrayList.size() - 1);

                                                        offset += Integer.parseInt(Constant.LOAD_ITEM_LIMIT);

                                                        Map<String, String> params = new HashMap<>();
                                                        params.put(Constant.ID, session.getData(Constant.ID));
                                                        params.put(Constant.GET_ORDERS_BY_DELIVERY_BOY_ID, Constant.GetVal);
                                                        params.put(Constant.LIMIT, "" + Constant.LOAD_ITEM_LIMIT);
                                                        params.put(Constant.OFFSET, "" + offset);

                                                        ApiConfig.RequestToVolley(new VolleyCallback() {
                                                            @Override
                                                            public void onSuccess(boolean result, String response) {

                                                                if (result) {
                                                                    try {
                                                                        // System.out.println("====product  " + response);
                                                                        JSONObject objectbject1 = new JSONObject(response);
                                                                        if (!objectbject1.getBoolean(Constant.ERROR)) {

                                                                            session.setData(Constant.TOTAL, objectbject1.getString(Constant.TOTAL));

                                                                            orderListArrayList.remove(orderListArrayList.size() - 1);
                                                                            orderListAdapter.notifyItemRemoved(orderListArrayList.size());

                                                                            JSONObject object = new JSONObject(response);
                                                                            JSONArray jsonArray = object.getJSONArray(Constant.DATA);

                                                                            Gson g = new Gson();


                                                                            for (int i = 0; i < jsonArray.length(); i++) {
                                                                                JSONObject jsonObject1 = jsonArray.getJSONObject(i);

                                                                                if (jsonObject1 != null) {
                                                                                    OrderList orderList = g.fromJson(jsonObject1.toString(), OrderList.class);
                                                                                    orderListArrayList.add(orderList);
                                                                                } else {
                                                                                    break;
                                                                                }

                                                                            }
                                                                            orderListAdapter.notifyDataSetChanged();
                                                                            orderListAdapter.setLoaded();
                                                                            isLoadMore = false;
                                                                        }
                                                                    } catch (JSONException e) {
                                                                        e.printStackTrace();
                                                                    }
                                                                }
                                                            }
                                                        }, activity, Constant.MAIN_URL, params, false);

                                                        isLoadMore = true;
                                                    }

                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    } catch (JSONException e) {
                        e.printStackTrace();
                    }
                }
            }
        }, activity, Constant.MAIN_URL, params, false);
    }

    public void getTotalOrderCount(final Activity activity) {
        if (AppController.isConnected(activity)) {

            Map<String, String> params = new HashMap<String, String>();
            params.put(Constant.ID, session.getData(Constant.ID));
            params.put(Constant.GET_ORDERS_BY_DELIVERY_BOY_ID, Constant.GetVal);

            ApiConfig.RequestToVolley(new VolleyCallback() {
                @Override
                public void onSuccess(boolean result, String response) {
                    if (result) {
                        try {
                            JSONObject jsonObject = new JSONObject(response);

                            if (!jsonObject.getBoolean(Constant.ERROR)) {
                                session.setData(Constant.TOTAL, jsonObject.getString(Constant.TOTAL));

                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }
            }, activity, Constant.MAIN_URL, params, true);

        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry));
        }
    }

    public void getDeliveryBoyData(final Activity activity) {
        if (AppController.isConnected(activity)) {

            Map<String, String> params = new HashMap<String, String>();
            params.put(Constant.ID, session.getData(Constant.ID));
            params.put(Constant.GET_DELIVERY_BOY_BY_ID, Constant.GetVal);

            ApiConfig.RequestToVolley(new VolleyCallback() {
                @Override
                public void onSuccess(boolean result, String response) {
                    //  System.out.println("============" + response);
                    if (result) {
                        try {
                            JSONObject jsonObject = new JSONObject(response);
                            if (!jsonObject.getBoolean(Constant.ERROR)) {
                                StartMainActivity(activity, jsonObject.getJSONArray(Constant.DATA).getJSONObject(0));
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }
            }, activity, Constant.MAIN_URL, params, false);
        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry));
        }
    }

    @SuppressLint("SetTextI18n")
    public void StartMainActivity(Activity activity, JSONObject jsonObject) {
        if (AppController.isConnected(activity)) {
            try {
                new Session(activity).createUserLoginSession(
                        jsonObject.getString(Constant.FCM_ID),
                        jsonObject.getString(Constant.ID),
                        jsonObject.getString(Constant.NAME),
                        jsonObject.getString(Constant.MOBILE),
                        jsonObject.getString(Constant.PASSWORD),
                        jsonObject.getString(Constant.ADDRESS),
                        jsonObject.getString(Constant.BONUS),
                        jsonObject.getString(Constant.BALANCE),
                        jsonObject.getString(Constant.STATUS),
                        jsonObject.getString(Constant.IS_AVAILABLE),
                        jsonObject.getString(Constant.CREATED_AT));

                session.setData(Constant.ID, jsonObject.getString(Constant.ID));

                getTotalOrderCount(activity);

                tvOrdersCount.setText(session.getData(Constant.TOTAL));
                tvBalanceCount.setText(new Session(activity).getData(Constant.CURRENCY) + session.getData(Constant.BALANCE));
                tvBonusCount.setText(session.getData(Constant.BONUS) + " %");

                tvOrdersCount.setVisibility(View.VISIBLE);
                tvBalanceCount.setVisibility(View.VISIBLE);
                tvBonusCount.setVisibility(View.VISIBLE);

            } catch (JSONException e) {
                e.printStackTrace();
            }
        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry));
        }
    }

    @Override
    public void onBackPressed() {
        if (drawer.isDrawerOpen(navigationView))
            drawer.closeDrawers();
        else
            doubleBack();
    }

    public void doubleBack() {

        if (doubleBackToExitPressedOnce) {
            super.onBackPressed();
            return;
        }
        this.doubleBackToExitPressedOnce = true;
        Toast.makeText(this, getString(R.string.please_click_back_again_to_exit), Toast.LENGTH_SHORT).show();
        new Handler().postDelayed(new Runnable() {
            @Override
            public void run() {
                doubleBackToExitPressedOnce = false;
            }
        }, 2000);
    }

    public void setSnackBar(final Activity activity, String message, String action) {
        final Snackbar snackbar = Snackbar.make(activity.findViewById(android.R.id.content), message, Snackbar.LENGTH_INDEFINITE);
        snackbar.setAction(action, new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                snackbar.dismiss();
            }
        });
        snackbar.setActionTextColor(Color.RED);
        View snackbarView = snackbar.getView();
        TextView textView = snackbarView.findViewById(R.id.snackbar_text);
        textView.setMaxLines(5);
        snackbar.show();
    }

    public void enableDisableDeliveryBoy(String status) {
        Map<String, String> params = new HashMap<String, String>();
        params.put(Constant.DELIVERY_BOY_ID, session.getData(Constant.ID));
        params.put(Constant.UPDATE_DELIVERY_BOY_IS_AVAILABLE, Constant.GetVal);
        params.put(Constant.IS_AVAILABLE, status);

        ApiConfig.RequestToVolley(new VolleyCallback() {
            @Override
            public void onSuccess(boolean result, String response) {
                //  System.out.println("============" + response);
                if (result) {
                    try {
                        JSONObject jsonObject = new JSONObject(response);
                        String message = getString(R.string.delivery_boy_status) + ((status.equals("1") ? getString(R.string.enabled) : getString(R.string.disabled)) + getString(R.string.successfully));
                        if (!jsonObject.getBoolean(Constant.ERROR)) {
                            if (status.equals("1")) {
                                menu.findItem(R.id.toolbar_action).setIcon(R.drawable.ic_action_off);
                            } else {
                                menu.findItem(R.id.toolbar_action).setIcon(R.drawable.ic_action_on);
                            }
                            Toast.makeText(activity, message, Toast.LENGTH_SHORT).show();
                            session.setData(Constant.IS_AVAILABLE, status);
                            invalidateOptionsMenu();
                        }
                    } catch (JSONException e) {
                        e.printStackTrace();
                    }
                }
            }
        }, activity, "https://theecart.wrteam.in/delivery-boy/api/api-v1.php", params, false);
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == R.id.toolbar_action) {
            enableDisableDeliveryBoy(session.getData(Constant.IS_AVAILABLE).equals("1") ? "0" : "1");
        }
        return super.onOptionsItemSelected(item);
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.toolbar_menu, menu);
        return true;
    }

    @Override
    public boolean onPrepareOptionsMenu(Menu menu) {
        this.menu = menu;
        menu.findItem(R.id.toolbar_filter).setVisible(false);
        menu.findItem(R.id.toolbar_action).setVisible(true);
        if (session.getData(Constant.IS_AVAILABLE).equals("1")) {
            menu.findItem(R.id.toolbar_action).setIcon(R.drawable.ic_action_off);
        } else {
            menu.findItem(R.id.toolbar_action).setIcon(R.drawable.ic_action_on);
        }
        return super.onPrepareOptionsMenu(menu);
    }

    @Override
    public void onResume() {
        super.onResume();
        if (Constant.CLICK) {
            orderListAdapter.notifyDataSetChanged();
            Constant.CLICK = false;
        }
    }
}
