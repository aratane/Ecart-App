package wrteam.ecart.dboy.activity;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.Context;
import android.content.DialogInterface;
import android.graphics.drawable.ColorDrawable;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.widget.NestedScrollView;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.gson.Gson;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.Objects;

import wrteam.ecart.dboy.R;
import wrteam.ecart.dboy.adapter.WalletHistoryAdapter;
import wrteam.ecart.dboy.helper.ApiConfig;
import wrteam.ecart.dboy.helper.Constant;
import wrteam.ecart.dboy.helper.Session;
import wrteam.ecart.dboy.helper.VolleyCallback;
import wrteam.ecart.dboy.model.WalletHistory;

public class WalletHistoryActivity extends AppCompatActivity {


    public Session session;

    ArrayList<WalletHistory> walletHistories;
    WalletHistoryAdapter walletHistoryAdapter;
    TextView tvBalance;
    RecyclerView recyclerViewWalletHistory;
    Toolbar toolbar;
    Activity activity;
    SwipeRefreshLayout lyt_wallet_history_activity_swipe_refresh;
    int total = 0;
    Button btnSendWithdrawalRequest;
    private boolean isLoadMore = false;
    private NestedScrollView scrollView;
    int offset = 0;
    String data_type = Constant.FUND_TRANSFERS;
    private int filterIndex;

    @SuppressLint("SetTextI18n")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_wallet_history);


        activity = WalletHistoryActivity.this;
        session = new Session(activity);
        toolbar = findViewById(R.id.toolbar);
        tvBalance = findViewById(R.id.tvBalance);
        btnSendWithdrawalRequest = findViewById(R.id.btnSendWithdrawalRequest);
        lyt_wallet_history_activity_swipe_refresh = findViewById(R.id.lyt_wallet_history_activity_swipe_refresh);
        scrollView = findViewById(R.id.scrollView);


        recyclerViewWalletHistory = findViewById(R.id.recyclerViewWalletHistory);
        recyclerViewWalletHistory.setLayoutManager(new LinearLayoutManager(activity));

        tvBalance.setText(new Session(activity).getData(Constant.CURRENCY) + Constant.formatter.format(Double.parseDouble(session.getData(Constant.BALANCE))));

        setSupportActionBar(toolbar);
        getSupportActionBar().setTitle(R.string.wallet_history);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        getWalletHistory();

        lyt_wallet_history_activity_swipe_refresh.setOnRefreshListener(new SwipeRefreshLayout.OnRefreshListener() {
            @Override
            public void onRefresh() {
                lyt_wallet_history_activity_swipe_refresh.setRefreshing(false);
                offset = 0;
                getWalletHistory();
            }
        });

        btnSendWithdrawalRequest.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                final AlertDialog.Builder alertDialog = new AlertDialog.Builder(WalletHistoryActivity.this);
                LayoutInflater inflater = (LayoutInflater) WalletHistoryActivity.this.getSystemService(Context.LAYOUT_INFLATER_SERVICE);
                final View dialogView = inflater.inflate(R.layout.dialog_confirm_send_request, null);
                alertDialog.setView(dialogView);
                alertDialog.setCancelable(true);
                final AlertDialog dialog = alertDialog.create();
                Objects.requireNonNull(dialog.getWindow()).setBackgroundDrawable(new ColorDrawable(android.graphics.Color.TRANSPARENT));

                TextView tvDialogSend = dialogView.findViewById(R.id.tvDialogSend);
                TextView tvDialogCancel = dialogView.findViewById(R.id.tvDialogCancel);
                final EditText edtAmount = dialogView.findViewById(R.id.edtAmount);
                final EditText edtMsg = dialogView.findViewById(R.id.edtMsg);

                tvDialogSend.setOnClickListener(new View.OnClickListener() {
                    @Override
                    public void onClick(View v) {

                        if (!edtAmount.getText().toString().isEmpty() || edtAmount.getText().toString().equals("0")) {
                            if (Double.parseDouble(edtAmount.getText().toString()) <= Double.parseDouble(session.getData(Constant.BALANCE))) {
                                SendWithdrawalRequest(edtAmount.getText().toString().trim(), edtMsg.getText().toString().trim());
                                dialog.dismiss();
                            } else {
                                Toast.makeText(getApplicationContext(), R.string.alert_balance_limit, Toast.LENGTH_SHORT).show();
                            }
                        } else {
                            edtAmount.setError(getString(R.string.alert_enter_amount));
                        }
                    }
                });

                tvDialogCancel.setOnClickListener(new View.OnClickListener() {
                    @Override
                    public void onClick(View v) {
                        dialog.dismiss();
                    }
                });

                dialog.show();
            }
        });
    }

    public void SendWithdrawalRequest(String amount, String message) {

        Map<String, String> params = new HashMap<>();
        params.put(Constant.ID, session.getData(Constant.ID));
        params.put(Constant.SEND_WITHDRAWAL_REQUEST, Constant.GetVal);
        params.put(Constant.TYPE, Constant.DELIVERY_BOY);
        params.put(Constant.AMOUNT, amount);
        params.put(Constant.MESSAGE, message);

        ApiConfig.RequestToVolley(new VolleyCallback() {
            @SuppressLint("SetTextI18n")
            @Override
            public void onSuccess(boolean result, String response) {

                if (result) {
                    try {
                        JSONObject objectbject1 = new JSONObject(response);
                        if (!objectbject1.getBoolean(Constant.ERROR)) {
                            String update_balance = objectbject1.getString(Constant.UPDATED_BALANCE);

                            tvBalance.setText(update_balance);
                            DrawerActivity.tvWallet.setCompoundDrawablesWithIntrinsicBounds(R.drawable.ic_wallet_white, 0, 0, 0);
                            DrawerActivity.tvWallet.setText(getString(R.string.wallet_balance) + "\t:\t" + new Session(activity).getData(Constant.CURRENCY) + update_balance);
                            session.setData(Constant.BALANCE, objectbject1.getString(Constant.UPDATED_BALANCE));
                        }
                    } catch (JSONException e) {
                        e.printStackTrace();
                    }
                }
            }
        }, activity, Constant.MAIN_URL, params, false);
    }

    private void getWalletHistory() {
        walletHistories = new ArrayList<>();
        final LinearLayoutManager linearLayoutManager = new LinearLayoutManager(activity);
        recyclerViewWalletHistory.setLayoutManager(linearLayoutManager);

        Map<String, String> params = new HashMap<String, String>();
        params.put(Constant.TYPE_ID, session.getData(Constant.ID));
        params.put(Constant.GET_WITHDRAWAL_REQUEST, Constant.GetVal);
        params.put(Constant.OFFSET, "" + offset);
        params.put(Constant.TYPE, Constant.DELIVERY_BOY);
        params.put(Constant.LIMIT, Constant.PRODUCT_LOAD_LIMIT);
        params.put(Constant.DATA_TYPE, data_type);


        ApiConfig.RequestToVolley(new VolleyCallback() {
            @Override
            public void onSuccess(boolean result, String response) {
                if (result) {
                    try {
                        JSONObject objectbject = new JSONObject(response);
                        if (!objectbject.getBoolean(Constant.ERROR)) {
                            total = Integer.parseInt(objectbject.getString(Constant.TOTAL));
                            session.setData(Constant.TOTAL, String.valueOf(total));

                            JSONObject object = new JSONObject(response);
                            JSONArray jsonArray = object.getJSONArray(Constant.DATA);

                            Gson g = new Gson();

                            for (int i = 0; i < jsonArray.length(); i++) {
                                JSONObject jsonObject1 = jsonArray.getJSONObject(i);

                                if (jsonObject1 != null) {
                                    WalletHistory notification = g.fromJson(jsonObject1.toString(), WalletHistory.class);
                                    walletHistories.add(notification);
                                } else {
                                    break;
                                }

                            }
                            if (offset == 0) {
                                walletHistoryAdapter = new WalletHistoryAdapter(activity, walletHistories);
                                walletHistoryAdapter.setHasStableIds(true);
                                recyclerViewWalletHistory.setAdapter(walletHistoryAdapter);
                                scrollView.setOnScrollChangeListener(new NestedScrollView.OnScrollChangeListener() {
                                    @Override
                                    public void onScrollChange(NestedScrollView v, int scrollX, int scrollY, int oldScrollX, int oldScrollY) {

                                        if (scrollY == (v.getChildAt(0).getMeasuredHeight() - v.getMeasuredHeight())) {
                                            LinearLayoutManager linearLayoutManager = (LinearLayoutManager) recyclerViewWalletHistory.getLayoutManager();
                                            if (walletHistories.size() < total) {
                                                if (!isLoadMore) {
                                                    if (linearLayoutManager != null && linearLayoutManager.findLastCompletelyVisibleItemPosition() == walletHistories.size() - 1) {
                                                        walletHistories.add(null);
                                                        walletHistoryAdapter.notifyItemInserted(walletHistories.size() - 1);

                                                        offset += Integer.parseInt(Constant.LOAD_ITEM_LIMIT);

                                                        Map<String, String> params = new HashMap<String, String>();
                                                        params.put(Constant.TYPE_ID, session.getData(Constant.ID));
                                                        params.put(Constant.GET_WITHDRAWAL_REQUEST, Constant.GetVal);
                                                        params.put(Constant.OFFSET, "" + offset);
                                                        params.put(Constant.TYPE, Constant.DELIVERY_BOY);
                                                        params.put(Constant.LIMIT, Constant.PRODUCT_LOAD_LIMIT);
                                                        params.put(Constant.DATA_TYPE, data_type);


                                                        ApiConfig.RequestToVolley(new VolleyCallback() {
                                                            @Override
                                                            public void onSuccess(boolean result, String response) {

                                                                if (result) {
                                                                    try {
                                                                        JSONObject objectbject1 = new JSONObject(response);
                                                                        if (!objectbject1.getBoolean(Constant.ERROR)) {

                                                                            session.setData(Constant.TOTAL, objectbject1.getString(Constant.TOTAL));

                                                                            walletHistories.remove(walletHistories.size() - 1);
                                                                            walletHistoryAdapter.notifyItemRemoved(walletHistories.size());

                                                                            JSONObject object = new JSONObject(response);
                                                                            JSONArray jsonArray = object.getJSONArray(Constant.DATA);

                                                                            Gson g = new Gson();


                                                                            for (int i = 0; i < jsonArray.length(); i++) {
                                                                                JSONObject jsonObject1 = jsonArray.getJSONObject(i);

                                                                                if (jsonObject1 != null) {
                                                                                    WalletHistory notification = g.fromJson(jsonObject1.toString(), WalletHistory.class);
                                                                                    walletHistories.add(notification);
                                                                                } else {
                                                                                    break;
                                                                                }

                                                                            }
                                                                            walletHistoryAdapter.notifyDataSetChanged();
                                                                            walletHistoryAdapter.setLoaded();
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
        }, activity, Constant.MAIN_URL, params, true);
        if (walletHistoryAdapter != null) {
            walletHistoryAdapter.notifyDataSetChanged();
        }
    }


    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == R.id.toolbar_filter) {
            AlertDialog.Builder builder = new AlertDialog.Builder(activity);
            builder.setTitle(activity.getResources().getString(R.string.filterby));
            builder.setSingleChoiceItems(Constant.filtervalues, filterIndex, new DialogInterface.OnClickListener() {
                public void onClick(DialogInterface dialog, int item) {
                    filterIndex = item;
                    switch (item) {
                        case 0:
                            data_type = Constant.FUND_TRANSFERS;
                            break;
                        case 1:
                            data_type = Constant.WITHDRAWAL_REQUEST;
                            break;
                    }
                    getWalletHistory();
                    dialog.dismiss();
                }
            });
            AlertDialog alertDialog = builder.create();
            alertDialog.show();
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
        return super.onPrepareOptionsMenu(menu);
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }

    @Override
    public void onBackPressed() {
        super.onBackPressed();

    }
}