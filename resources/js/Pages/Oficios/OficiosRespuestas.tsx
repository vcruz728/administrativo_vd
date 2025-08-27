import AppLayout from "@/Layouts/app";
import { Head, Link, useForm } from "@inertiajs/react";
import { useState, Fragment, useRef, FormEventHandler, useEffect } from "react";
import PageHeader from "../../Layouts/layoutcomponents/pageHeader";
import {
    Button,
    Card,
    Col,
    Form,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    ModalTitle,
    Row,
    Tab,
    Tabs,
} from "react-bootstrap";
// @ts-ignore
import language from "datatables.net-plugins/i18n/es-MX.mjs";
import InputError from "../InputError";
import toast from "react-hot-toast";
import VerPdf from "@/types/VerPdf";
import LineaTiempo from "@/types/LineaTiempo";

import $ from "jquery";
import DataTable from "datatables.net-react";
import DT from "datatables.net-bs5";

// Importa JSZip y asigna a window si es necesario
import JSZip from "jszip";
// @ts-ignore
window.JSZip = JSZip;

// Importa los botones de DataTables
import "datatables.net-buttons-bs5";
import "datatables.net-buttons/js/buttons.html5.js";

import "../../../css/botones.css";

DataTable.use(DT);

type FormIn = {
    id: number;
    archivo: File | null;
    tipo: string;
};

const OficiosRespuestas = ({
    status,
    oficios,
    nuevos,
}: {
    status?: string;
    oficios: [];
    nuevos: [];
}) => {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [showPdf, setShowPdf] = useState(false); // visor PDF (nuevo)
    const [showUpload, setShowUpload] = useState(false); // subir confirmación
    const [showLinea, setShowLinea] = useState(false);
    const [variables, setVariables] = useState({
        urlPdf: "",
        extension: "",
        idOfico: 0,
    });

    const [activos, setActivos] = useState<any[]>();
    const [historico, setHistorico] = useState<any[]>();
    const [informativos, setInformativos] = useState<any[]>();
    const [nuevoOfi, setNuevoOfi] = useState<any[]>();
    const [nuevoHistorico, setNuevoHistorico] = useState<any[]>();

    useEffect(() => {
        setActivos(
            (oficios || [])
                .filter(
                    (item: any) =>
                        item.archivo_respuesta === null && item.id_area !== "1"
                )
                .map((file: any) => ({
                    ...file,
                }))
        );
        setHistorico(
            (oficios || [])
                .filter(
                    (item: any) =>
                        item.archivo_respuesta !== null && item.id_area !== "1"
                )
                .map((file: any) => ({
                    ...file,
                }))
        );

        setInformativos(
            (oficios || [])
                .filter((item: any) => item.id_area === "1")
                .map((file: any) => ({
                    ...file,
                }))
        );
    }, [oficios]);

    useEffect(() => {
        setNuevoOfi(
            (nuevos || [])
                .filter((item: any) => item.archivo_respuesta === null)
                .map((file: any) => ({
                    ...file,
                }))
        );
        setNuevoHistorico(
            (nuevos || [])
                .filter((item: any) => item.archivo_respuesta !== null)
                .map((file: any) => ({
                    ...file,
                }))
        );
    }, [nuevos]);

    const form = useForm<FormIn>({
        id: 0,
        archivo: null,
        tipo: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route("subeEvidenciaRecibido"), {
            onSuccess: clearForm,
        });
    };

    const handleChangeS = (e: any) => {
        const target = e.target as HTMLInputElement;
        if (target.files) {
            form.setData("archivo", target.files[0]);
        }
    };

    const clearForm = () => {
        form.setDefaults({
            id: 0,
            archivo: null,
        });
        setShowUpload(false);

        form.reset();

        fileInputRef.current!.value = "";

        toast("Correcto: Se guardo la respuesta del oficio.", {
            style: {
                padding: "25px",
                color: "#fff",
                backgroundColor: "#29bf74",
            },
            position: "top-center",
        });
    };

    const filtroTabla = async (valor: string, tipo: string) => {
        const response = await fetch(
            route("oficios.getEstatus", {
                valor: valor,
                tipo: tipo,
            }),
            {
                method: "get",
            }
        );

        const datos = await response.json();

        switch (tipo) {
            case "activos":
                setActivos(
                    (datos.data || [])
                        .filter(
                            (item: any) =>
                                item.archivo_respuesta === null &&
                                item.id_area !== "1"
                        )
                        .map((file: any) => ({
                            ...file,
                        }))
                );
                break;
            case "historico_vd":
                setHistorico(
                    (datos.data || [])
                        .filter(
                            (item: any) =>
                                item.archivo_respuesta !== null &&
                                item.id_area !== "1"
                        )
                        .map((file: any) => ({
                            ...file,
                        }))
                );
                break;
        }
    };

    return (
        <AppLayout>
            <Head>
                <title>Mis oficios</title>
                <meta
                    name="Oficios con respuestas"
                    content="Lista de oficios con respuestas asignadas."
                />
            </Head>
            <Fragment>
                <PageHeader
                    titles="Listado de oficios"
                    active="Listado de oficios"
                    items={[]}
                />
                <Row>
                    <Col lg={12} md={12}>
                        <Card>
                            <Card.Header className="d-flex justify-content-between">
                                <Card.Title as="h3">
                                    Oficios con respuestas
                                </Card.Title>
                                <div className="tags">
                                    <span className="tag tag-radius tag-round tag-verde">
                                        Se dio respuesta en tiempo
                                    </span>

                                    <span className="tag tag-radius tag-round tag-amarillo">
                                        Sin respuesta, en tiempo
                                    </span>

                                    <span className="tag tag-radius tag-round tag-naranja">
                                        Sin respuesta, fuera de tiempo
                                    </span>

                                    <span className="tag tag-radius tag-round tag-rojo">
                                        Se dio respuesta fuera de tiempo
                                    </span>
                                </div>
                                <div className="tags"></div>
                            </Card.Header>
                            <Card.Body>
                                <div className="panel panel-default">
                                    <Tabs defaultActiveKey="tab1">
                                        <Tab eventKey="tab1" title="Activos">
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-between">
                                                    <div
                                                        style={{ width: "20%" }}
                                                    >
                                                        <div
                                                            className="form-group"
                                                            style={{
                                                                margin: 0,
                                                            }}
                                                        >
                                                            <select
                                                                className="form-control"
                                                                onChange={(e) =>
                                                                    filtroTabla(
                                                                        e.target
                                                                            .value,
                                                                        "activos"
                                                                    )
                                                                }
                                                            >
                                                                <option value="0">
                                                                    Todos
                                                                </option>
                                                                <option value="1">
                                                                    Se dio
                                                                    respuesta en
                                                                    tiempo
                                                                </option>
                                                                <option value="2">
                                                                    Sin
                                                                    respuesta,
                                                                    en tiempo
                                                                </option>
                                                                <option value="3">
                                                                    Sin
                                                                    respuesta,
                                                                    fuera de
                                                                    tiempo
                                                                </option>
                                                                <option value="4">
                                                                    Se dio
                                                                    respuesta
                                                                    fuera de
                                                                    tiempo
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table =
                                                                $(
                                                                    "#activos-table"
                                                                ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    id="activos-table"
                                                    data={activos}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        order: [[1, "desc"]],
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        1, 2, 3,
                                                                        4, 5,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "id",
                                                            title: "Estatus",

                                                            createdCell(
                                                                cell,
                                                                cellData,
                                                                rowData,
                                                                row,
                                                                col
                                                            ) {
                                                                $(cell).css(
                                                                    "background",
                                                                    rowData.color
                                                                );
                                                                $(cell).css(
                                                                    "color",
                                                                    rowData.color
                                                                );
                                                            },
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de ingreso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "numero_oficio",
                                                            title: "No. Oficio / Folio",
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            title: "Proceso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "descripcion",
                                                            title: "Breve descripción",
                                                            width: "40%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "asunto",
                                                            title: "Respuesta",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        6: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div
                                                                className="btns-acciones"
                                                                onClick={(e) =>
                                                                    e.stopPropagation()
                                                                }
                                                            >
                                                                {/* 1) Subir confirmación / Revisar respuesta */}
                                                                {row.enviado ===
                                                                1 ? (
                                                                    <Button
                                                                        type="button"
                                                                        className="btn-icon"
                                                                        variant="warning"
                                                                        title="Subir confirmación de recibido"
                                                                        onClick={(
                                                                            e
                                                                        ) => {
                                                                            e.preventDefault();
                                                                            e.stopPropagation();
                                                                            form.clearErrors();
                                                                            form.setData(
                                                                                {
                                                                                    ...data,
                                                                                    id: row.id,
                                                                                    tipo: "respuesta",
                                                                                }
                                                                            );
                                                                            setShowUpload(
                                                                                true
                                                                            );
                                                                        }}
                                                                    >
                                                                        <i className="fa fa-upload"></i>
                                                                    </Button>
                                                                ) : (
                                                                    <Link
                                                                        href={route(
                                                                            "viewRespOficio",
                                                                            {
                                                                                id: row.id,
                                                                            }
                                                                        )}
                                                                        className="btn btn-icon btn-primary"
                                                                        title="Revisar respuesta"
                                                                        onClick={(
                                                                            e
                                                                        ) =>
                                                                            e.stopPropagation()
                                                                        }
                                                                    >
                                                                        <i className="zmdi zmdi-pin-account"></i>
                                                                    </Link>
                                                                )}

                                                                {/* 2) Línea de tiempo */}
                                                                <Button
                                                                    type="button"
                                                                    className="btn-icon"
                                                                    variant="success"
                                                                    title="Ver línea de tiempo del oficio"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        e.preventDefault();
                                                                        e.stopPropagation();
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                idOfico:
                                                                                    row.id,
                                                                            }
                                                                        );
                                                                        setShowLinea(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-history"></i>
                                                                </Button>

                                                                {/* 3) Ver respuesta al oficio (PDF) — solo si enviado===1 */}
                                                                {row.enviado ===
                                                                    1 && (
                                                                    <Button
                                                                        type="button"
                                                                        className="btn-icon"
                                                                        variant="danger"
                                                                        title="Ver respuesta al oficio"
                                                                        onClick={(
                                                                            e
                                                                        ) => {
                                                                            e.preventDefault();
                                                                            e.stopPropagation();
                                                                            setVariables(
                                                                                {
                                                                                    ...variables,
                                                                                    urlPdf: `imprime/pdf/0/${row.id}`,
                                                                                    extension:
                                                                                        "pdf",
                                                                                }
                                                                            );
                                                                            setShowPdf(
                                                                                true
                                                                            );
                                                                        }}
                                                                    >
                                                                        <i className="fa fa-file-pdf-o"></i>
                                                                    </Button>
                                                                )}

                                                                {/* 4) Ver PDF del oficio (siempre) */}
                                                                <Button
                                                                    type="button"
                                                                    className="btn-icon"
                                                                    variant="danger"
                                                                    title="Ver PDF del oficio"
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        e.preventDefault();
                                                                        e.stopPropagation();
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.archivo,
                                                                                extension:
                                                                                    "pdf",
                                                                            }
                                                                        );
                                                                        setShowPdf(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-eye"></i>
                                                                </Button>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                        <Tab eventKey="tab2" title="Histórico">
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-between">
                                                    <div
                                                        style={{ width: "20%" }}
                                                    >
                                                        <div
                                                            className="form-group"
                                                            style={{
                                                                margin: 0,
                                                            }}
                                                        >
                                                            <select
                                                                className="form-control"
                                                                onChange={(e) =>
                                                                    filtroTabla(
                                                                        e.target
                                                                            .value,
                                                                        "historico_vd"
                                                                    )
                                                                }
                                                            >
                                                                <option value="0">
                                                                    Todos
                                                                </option>
                                                                <option value="1">
                                                                    Se dio
                                                                    respuesta en
                                                                    tiempo
                                                                </option>
                                                                <option value="4">
                                                                    Se dio
                                                                    respuesta
                                                                    fuera de
                                                                    tiempo
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table =
                                                                $(
                                                                    "#historico-table"
                                                                ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    id="historico-table"
                                                    data={historico}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        order: [[1, "desc"]],
                                                        lengthMenu: [
                                                            [25, 50, 100],
                                                            [25, 50, 100],
                                                        ],
                                                        pageLength: 25,
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        1, 2, 3,
                                                                        4, 5,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "id",
                                                            title: "Estatus",

                                                            createdCell(
                                                                cell,
                                                                cellData,
                                                                rowData,
                                                                row,
                                                                col
                                                            ) {
                                                                $(cell).css(
                                                                    "background",
                                                                    rowData.color
                                                                );
                                                                $(cell).css(
                                                                    "color",
                                                                    rowData.color
                                                                );
                                                            },
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de ingreso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "numero_oficio",
                                                            title: "No. Oficio / Folio",
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            title: "Proceso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "descripcion",
                                                            title: "Breve descripción",
                                                            width: "40%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "asunto",
                                                            title: "Respuesta",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        6: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div className="text-center">
                                                                <Button
                                                                    className="btn-icon btn btn-warning"
                                                                    variant="danger"
                                                                    title="Ver confirmación de recibido"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.archivo_respuesta,
                                                                                extension:
                                                                                    row.extension,
                                                                            }
                                                                        );
                                                                        setShowPdf(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-file-pdf-o"></i>
                                                                </Button>

                                                                <Button
                                                                    className="btn-icon btn btn-warning ml-1"
                                                                    variant="danger"
                                                                    title="Ver respuesta al oficio"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.oficio_final,
                                                                                extension:
                                                                                    "pdf",
                                                                            }
                                                                        );

                                                                        setShowPdf(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-file-pdf-o"></i>
                                                                </Button>

                                                                <Button
                                                                    className="btn-icon ml-1"
                                                                    variant="danger"
                                                                    title="Ver PDF del oficio"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.archivo,
                                                                                extension:
                                                                                    "pdf",
                                                                            }
                                                                        );

                                                                        setShowPdf(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-eye"></i>
                                                                </Button>

                                                                <Button
                                                                    className="btn-icon ml-1"
                                                                    variant="success"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                idOfico:
                                                                                    row.id,
                                                                            }
                                                                        );
                                                                        setShowLinea(
                                                                            true
                                                                        );
                                                                    }}
                                                                    title="Ver línea de tiempo del oficio"
                                                                >
                                                                    <i className="fa fa-history"></i>
                                                                </Button>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                        <Tab
                                            eventKey="tab3"
                                            title="Informativos Historicos"
                                        >
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <DataTable
                                                    data={informativos}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        order: [[0, "desc"]],
                                                        lengthMenu: [
                                                            [25, 50, 100],
                                                            [25, 50, 100],
                                                        ],
                                                        pageLength: 25,
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de ingreso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "numero_oficio",
                                                            title: "No. Oficio / Folio",
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "descripcion",
                                                            title: "Breve descripción",
                                                            width: "40%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        3: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div className="text-center">
                                                                <Button
                                                                    className="btn-icon "
                                                                    variant="danger"
                                                                    title="Ver PDF del oficio"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.archivo,
                                                                                extension:
                                                                                    "pdf",
                                                                            }
                                                                        );

                                                                        setShowPdf(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-eye"></i>
                                                                </Button>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                        <Tab eventKey="tab4" title="Oficios VD">
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-end">
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table =
                                                                $(
                                                                    "#vd-table"
                                                                ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    id="vd-table"
                                                    data={nuevoOfi}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        order: [[0, "desc"]],
                                                        lengthMenu: [
                                                            [25, 50, 100],
                                                            [25, 50, 100],
                                                        ],
                                                        pageLength: 25,
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        0, 1, 2,
                                                                        3,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de creación",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "oficio_respuesta",
                                                            title: "Num Folio/Oficio",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "nombre_desti",
                                                            title: "Destinatario",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "id",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "respuesta",
                                                            title: "Respuesta",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        4: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div
                                                                className="d-flex justify-content-center gap-1"
                                                                onClick={(e) =>
                                                                    e.stopPropagation()
                                                                }
                                                            >
                                                                {row.masivo ==
                                                                1 ? (
                                                                    row.enviado ===
                                                                    1 ? (
                                                                        row.archivo_respuesta ===
                                                                        null ? (
                                                                            <Button
                                                                                className="btn-icon btn btn-warning mr-1"
                                                                                variant="warning"
                                                                                title="Subir confirmación de recibido"
                                                                                onClick={() => {
                                                                                    form.clearErrors();
                                                                                    form.setData(
                                                                                        {
                                                                                            ...data,
                                                                                            id: row.id,
                                                                                            tipo: "nuevo",
                                                                                        }
                                                                                    );
                                                                                    setShowUpload(
                                                                                        true
                                                                                    );
                                                                                }}
                                                                            >
                                                                                <i className="fa fa-upload"></i>
                                                                            </Button>
                                                                        ) : (
                                                                            <Button
                                                                                className="btn-icon btn btn-warning mr-1"
                                                                                variant="danger"
                                                                                title="Ver confirmación de recibido"
                                                                                onClick={() => {
                                                                                    setVariables(
                                                                                        {
                                                                                            ...variables,
                                                                                            urlPdf: row.archivo_respuesta,
                                                                                            extension:
                                                                                                row.extension,
                                                                                        }
                                                                                    );
                                                                                    setShowPdf(
                                                                                        true
                                                                                    );
                                                                                }}
                                                                            >
                                                                                <i className="fa fa-file-pdf-o"></i>
                                                                            </Button>
                                                                        )
                                                                    ) : null
                                                                ) : row.enviado ===
                                                                  1 ? (
                                                                    <Link
                                                                        href={route(
                                                                            "oficios.confirmaRecibidosNuevos",
                                                                            {
                                                                                id: row.id,
                                                                            }
                                                                        )}
                                                                    >
                                                                        <Button
                                                                            className="btn-icon btn btn-warning mr-1"
                                                                            variant="warning"
                                                                            title="Confirmaciones de recibido"
                                                                        >
                                                                            <i className="fa fa-handshake-o"></i>{" "}
                                                                        </Button>
                                                                    </Link>
                                                                ) : null}

                                                                {row.enviado ===
                                                                null ? (
                                                                    <Link
                                                                        href={route(
                                                                            "viewRespOficio",
                                                                            {
                                                                                id: row.id,
                                                                            }
                                                                        )}
                                                                        className="btn btn-icon btn-warning mr-1"
                                                                        title="Revisar respuesta"
                                                                        onClick={(
                                                                            e
                                                                        ) =>
                                                                            e.stopPropagation()
                                                                        }
                                                                    >
                                                                        <i className="zmdi zmdi-pin-account"></i>
                                                                    </Link>
                                                                ) : null}

                                                                <Link
                                                                    href={route(
                                                                        "oficios.detalleNuevo",
                                                                        {
                                                                            id: row.id,
                                                                        }
                                                                    )}
                                                                >
                                                                    <Button
                                                                        className="btn-icon "
                                                                        variant="danger"
                                                                        title="Ver detalle del oficio"
                                                                    >
                                                                        <i className="fa fa-eye"></i>
                                                                    </Button>
                                                                </Link>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                        <Tab
                                            eventKey="tab5"
                                            title="Oficios VD Histórico"
                                        >
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-end">
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table = $(
                                                                "#vd-historico-table"
                                                            ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    id="vd-historico-table"
                                                    data={nuevoHistorico}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        order: [[0, "desc"]],
                                                        lengthMenu: [
                                                            [25, 50, 100],
                                                            [25, 50, 100],
                                                        ],
                                                        pageLength: 25,
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        0, 1, 2,
                                                                        3,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de creación",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "oficio_respuesta",
                                                            title: "Num Folio/Oficio",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "destinatario",
                                                            title: "Destinatario",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "id",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "respuesta",
                                                            title: "Respuesta",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        4: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div className="text-center">
                                                                {row.masivo ==
                                                                1 ? (
                                                                    <Button
                                                                        className="btn-icon btn btn-warning mr-1"
                                                                        variant="danger"
                                                                        title="Ver confirmación de recibido"
                                                                        onClick={() => {
                                                                            setVariables(
                                                                                {
                                                                                    ...variables,
                                                                                    urlPdf: row.archivo_respuesta,
                                                                                    extension:
                                                                                        row.extension,
                                                                                }
                                                                            );
                                                                            setShowPdf(
                                                                                true
                                                                            );
                                                                        }}
                                                                    >
                                                                        <i className="fa fa-file-pdf-o"></i>
                                                                    </Button>
                                                                ) : (
                                                                    <Link
                                                                        href={route(
                                                                            "oficios.confirmaRecibidosNuevos",
                                                                            {
                                                                                id: row.id,
                                                                            }
                                                                        )}
                                                                    >
                                                                        <Button
                                                                            className="btn-icon btn btn-warning mr-1"
                                                                            variant="warning"
                                                                            title="Confirmaciones de recibido"
                                                                        >
                                                                            <i className="fa fa-handshake-o"></i>{" "}
                                                                        </Button>
                                                                    </Link>
                                                                )}

                                                                <Link
                                                                    href={route(
                                                                        "oficios.detalleNuevo",
                                                                        {
                                                                            id: row.id,
                                                                        }
                                                                    )}
                                                                >
                                                                    <Button
                                                                        className="btn-icon "
                                                                        variant="danger"
                                                                        title="Ver detalle del oficio"
                                                                    >
                                                                        <i className="fa fa-eye"></i>
                                                                    </Button>
                                                                </Link>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                    </Tabs>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                <Modal show={showUpload} onHide={() => setShowUpload(false)}>
                    <ModalHeader>
                        <ModalTitle as="h5">Evidencía de recibido</ModalTitle>
                    </ModalHeader>
                    <form onSubmit={submit}>
                        <ModalBody>
                            <Row>
                                <Col xs={12}>
                                    <Form.Label>
                                        Evidencia de confirmación de recibido:
                                    </Form.Label>
                                    <Form.Control
                                        type="file"
                                        accept=".pdf,.jpg,.png,.jpeg"
                                        className={
                                            form.errors.archivo
                                                ? "inputError"
                                                : ""
                                        }
                                        ref={fileInputRef}
                                        onChange={(e) => handleChangeS(e)}
                                    />

                                    <InputError
                                        className="mt-1"
                                        message={form.errors.archivo}
                                    />
                                </Col>
                            </Row>
                        </ModalBody>
                        <ModalFooter>
                            <Button
                                variant="secondary"
                                onClick={() => setShowUpload(false)}
                            >
                                Cancelar
                            </Button>
                            <Button variant="primary" type="submit">
                                Subir evidencia
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                <VerPdf
                    urlPdf={variables.urlPdf}
                    show={showPdf}
                    tipo={variables.extension}
                    setShow={setShowPdf}
                />

                <LineaTiempo
                    showLinea={showLinea}
                    setShowLinea={setShowLinea}
                    id={variables.idOfico}
                />
            </Fragment>
        </AppLayout>
    );
};

export default OficiosRespuestas;
