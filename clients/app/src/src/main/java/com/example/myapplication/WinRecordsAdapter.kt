package com.example.myapplication

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class WinRecordsAdapter(private val records: MutableList<WinRecord>) : 
    RecyclerView.Adapter<WinRecordsAdapter.ViewHolder>() {
    
    class ViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val userText: TextView = view.findViewById(R.id.userText)
        val awardText: TextView = view.findViewById(R.id.awardText)
        val timeText: TextView = view.findViewById(R.id.timeText)
        val resultText: TextView = view.findViewById(R.id.resultText)
        val valueText: TextView = view.findViewById(R.id.valueText)
    }
    
    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_win_record, parent, false)
        return ViewHolder(view)
    }
    
    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val record = records[position]
        holder.userText.text = record.user
        holder.awardText.text = record.award
        holder.timeText.text = record.time
        holder.resultText.text = record.result
        holder.valueText.text = record.value
    }
    
    override fun getItemCount() = records.size
}
