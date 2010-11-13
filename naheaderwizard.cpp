#include "naheaderwizard.h"
#include "ui_naheaderwizard.h"

NAHeaderWizard::NAHeaderWizard(QWidget *parent) :
    QWizard(parent),
    ui(new Ui::NAHeaderWizard)
{
    ui->setupUi(this);
}

NAHeaderWizard::~NAHeaderWizard()
{
    delete ui;
}



void NAHeaderWizard::accept()
{
    header.append("name:"+ui->nameE->text()+";");
    if (!ui->commentaryE->text().isEmpty())
        header.append("commentary:"+ui->commentaryE->text()+";");
    if (!ui->annotation1E->text().isEmpty())
        header.append("annotation:"+ui->annotation1E->text()+";");
    if (!ui->annotation2E->text().isEmpty())
        header.append("annotation:"+ui->annotation2E->text()+";");
    if (!ui->fontE->text().isEmpty())
        header.append("gregoriotex-font:"+ui->fontE->text()+";");
    header.append("initial-style:"+ui->initialBox->text()+";");
    if (!ui->occasionE->text().isEmpty())
        header.append("occasion:"+ui->occasionE->text()+";");
    if (!ui->officePartE->text().isEmpty())
        header.append("office-part:"+ui->officePartE->text()+";");
    if (!ui->modeE->text().isEmpty())
        header.append("mode:"+ui->modeE->text()+";");
    if (!ui->meterE->text().isEmpty())
        header.append("meter:"+ui->meterE->text()+";");
    if (!ui->authorE->text().isEmpty())
        header.append("author:"+ui->authorE->text()+";");
    if (!ui->dateE->text().isEmpty())
        header.append("date:"+ui->dateE->text()+";");
    if (!ui->manuscriptE->text().isEmpty())
        header.append("manuscript:"+ui->manuscriptE->text()+";");
    if (!ui->referenceE->text().isEmpty())
        header.append("manuscript-reference:"+ui->referenceE->text()+";");
    if (!ui->storageE->text().isEmpty())
        header.append("manuscript-storage-place:"+ui->storageE->text()+";");
    if (!ui->bookE->text().isEmpty())
        header.append("book:"+ui->bookE->text()+";");
    if (!ui->arrangerE->text().isEmpty())
        header.append("arranger:"+ui->arrangerE->text()+";");
    if (!ui->transcriberE->text().isEmpty())
        header.append("transcriber:"+ui->transcriberE->text()+";");
    if (!ui->transcriptiondateE->text().isEmpty())
        header.append("transcription-date:"+ui->transcriptiondateE->text()+";");
    if (!ui->usernotesE->text().isEmpty())
        header.append("user-notes:"+ui->usernotesE->text()+";");
    if (!ui->gabccE->text().isEmpty())
        header.append("gabc-copyright:"+ui->gabccE->text()+";");
    if (!ui->scorecE->text().isEmpty())
        header.append("score-copyright:"+ui->scorecE->text()+";");
    header.append("%%");
    QDialog::accept();
}
